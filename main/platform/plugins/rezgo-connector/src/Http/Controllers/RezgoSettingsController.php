<?php

namespace Botble\RezgoConnector\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\RezgoConnector\Services\RezgoApiService;
use Botble\Setting\Facades\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class RezgoSettingsController extends BaseController
{
    public function __construct(protected RezgoApiService $rezgoApi)
    {
    }

    /**
     * Display the settings page.
     */
    public function index()
    {
        $this->pageTitle(trans('plugins/rezgo-connector::rezgo.settings_title'));

        $settings = [
            'rezgo_cid'     => Setting::get('rezgo_cid', ''),
            'rezgo_api_key' => '', // Never render the encrypted key in the form
            'rezgo_enabled' => Setting::get('rezgo_enabled', false),
        ];

        return view('plugins/rezgo-connector::settings', compact('settings'));
    }

    /**
     * Save settings to the Botble Setting store.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'rezgo_cid'     => 'required|string|max:50',
            'rezgo_api_key' => 'nullable|string|max:255',
            'rezgo_enabled' => 'nullable|boolean',
        ]);

        Setting::set('rezgo_cid', trim($request->input('rezgo_cid')));
        Setting::set('rezgo_enabled', (bool) $request->input('rezgo_enabled', false));

        // Only update the API key if a new one was provided
        $newKey = trim($request->input('rezgo_api_key', ''));
        if (! empty($newKey)) {
            Setting::set('rezgo_api_key', Crypt::encryptString($newKey));
        }

        Setting::save();

        return redirect()
            ->route('rezgo-connector.settings')
            ->with('success', trans('plugins/rezgo-connector::rezgo.settings_saved'));
    }

    /**
     * AJAX: Test the current Rezgo credentials.
     */
    public function testConnection()
    {
        try {
            $result = $this->rezgoApi->testConnection();
        } catch (\Throwable $e) {
            $result = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        }

        return response()->json($result);
    }
}
