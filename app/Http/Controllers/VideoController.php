<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VideoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Video::class, 'video');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Video $video): BinaryFileResponse
    {
        if (Auth::check()) {
            $this->authorize('view', $video);
        } else {
            // Enforce monitor token realm scoping
            $token = $request->header('X-API-TOKEN') ?? $request->query('api_token');
            $monitor = Monitor::where('api_token', $token)->first();

            if (! $monitor || $monitor->realm_id !== $video->realm_id) {
                abort(403, 'Unauthorized media access across tenants.');
            }
        }

        return response()->file(Storage::disk('public')->path(config('ads.vid_basepath').$video->path));
    }
}
