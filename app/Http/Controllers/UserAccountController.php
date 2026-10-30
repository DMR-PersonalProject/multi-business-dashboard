<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAccountController extends Controller
{

    public function show($id)
    {
        $user = Auth::user();

        // Additional checks can be added here
        if ($user->id != $id) {
            return redirect()->route('settings', ['id' => $user->id]);
        }

        return view('settings.account-show', compact('user'));
    }

    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        return view('settings.account-edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $rules=[
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'user_image' => ImageUpload::rules(),
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'initial' => 'required|string|max:255',
            'suffix' => 'nullable|string',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone_number' => 'required|string|max:255',
        ];
        
        
        if (!$user->user_image) {
            // Only require image if none exists
            $rules['user_image'] = ImageUpload::rules(required: true);
        }
    
        $validatedData = $request->validate($rules);
    
        // Handle image upload if new image is provided
        if ($request->hasFile('user_image')) {
            $fileName = ImageUpload::store($request->file('user_image'), 'user_images');
            $validatedData['user_image'] = $fileName;
        }
        
        $user->update($validatedData);

        // Assuming you have the user's ID stored in a variable, e.g., $userId
        $userId = $user->id;

        return redirect()->route('settings.account-show', ['id' => $userId])->with('success', 'Updated my account successfully.');


        
    }
}
