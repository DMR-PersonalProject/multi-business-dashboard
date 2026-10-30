<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleSheetService;
use App\Support\SheetFeedbackRows;

class FeedbackController extends Controller
{
    public function importFeedbackOne()
    {
        return $this->replaceFeedback('Kuwago1!A2:F', 'general.kuwago-one.feedbacks');
    }

    public function importFeedbackTwo()
    {
        return $this->replaceFeedback('Kuwago1!A2:F', 'general.kuwago-two.feedbacks');
    }

    /**
     * Read and check every sheet row first; replace the stored feedback only
     * if all rows are valid, inside one transaction.
     */
    private function replaceFeedback(string $range, string $redirectRoute)
    {
        try {
            // Resolved here so the list pages work without the service-account file
            $data = app(GoogleSheetService::class)->getSheetData($range);
        } catch (\Throwable $e) {
            Log::warning('Feedback import failed: '.$e->getMessage());
            return redirect()->route($redirectRoute)->with('failed', 'Could not read the feedback sheet. No data was changed.');
        }

        try {
            $rows = SheetFeedbackRows::map($data, fn (array $row) => [
                'name' => $row[1],
                'feedback_type' => $row[2],
                'dish' => $row[3],
                'comments' => $row[4],
                'rating' => $row[5],
            ]);
        } catch (\UnexpectedValueException $e) {
            return redirect()->route($redirectRoute)->with('failed', $e->getMessage().' No data was changed.');
        }

        DB::transaction(function () use ($rows) {
            Feedback::query()->delete();
            foreach ($rows as $row) {
                Feedback::create($row);
            }
        });

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Feedback imported successfully.');
    }

    public function index()
    {
        $feedback = Feedback::orderBy('feedback_date', 'desc')->get();
        $averageRating = $feedback->avg('rating');

        // Calculate the number of votes for each rating (1 to 5)
        $ratingCounts = [
            1 => $feedback->where('rating', 1)->count(),
            2 => $feedback->where('rating', 2)->count(),
            3 => $feedback->where('rating', 3)->count(),
            4 => $feedback->where('rating', 4)->count(),
            5 => $feedback->where('rating', 5)->count(),
        ];

        return view('feedback.index', compact('feedback', 'averageRating', 'ratingCounts'));
    }

    public function filterByDate(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $feedback = Feedback::whereBetween('feedback_date', [$request->start_date, $request->end_date])->get();
        $averageRating = $feedback->avg('rating');

        $ratingCounts = [
            1 => $feedback->where('rating', 1)->count(),
            2 => $feedback->where('rating', 2)->count(),
            3 => $feedback->where('rating', 3)->count(),
            4 => $feedback->where('rating', 4)->count(),
            5 => $feedback->where('rating', 5)->count(),
        ];

        return response()->json([
            'averageRating' => $averageRating,
            'ratingCounts' => $ratingCounts,
            'comments' => $feedback->where('feedback_type', 'Comment')->values(),
            'suggestions' => $feedback->where('feedback_type', 'Suggestion')->values(),
            'complaints' => $feedback->where('feedback_type', 'Complaint')->values(),
        ]);
    }
}
