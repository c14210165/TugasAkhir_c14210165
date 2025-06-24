<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail; // Import "jasa pengiriman" Laravel
use App\Mail\EmailNotification; 

class EmailController extends Controller
{
    // Ini adalah method yang akan dipanggil oleh Vue/Route
    public function send(Request $request)
    {
        // 1. Prepare the data for the Mailable
        $mailData = [
            'recipient_name' => $request->input('name'),    // Get data from the form input
            'message_content'  => $request->input('message')
        ];

        $recipientEmail = $request->input('email'); // The destination email address from the form

        // 2. Create a new Mailable instance, pass the data, and dispatch the email
        try {
            Mail::to($recipientEmail)->send(new EmailNotification($mailData));
            
            // Return a success response to the frontend (Vue)
            return response()->json(['message' => 'Email sent successfully!']);

        } catch (\Exception $e) {
            // If it fails, log the error (optional but recommended) and return an error response
            // \Log::error("Email sending failed: " . $e->getMessage());
            
            return response()->json(['message' => 'Failed to send email. Please try again later.'], 500);
        }
    }
}
