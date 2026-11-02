<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Native\Desktop\App;
use Native\Desktop\Shell;
use Native\Desktop\Windows\WindowManager;

/**
 * POST /api/app/*: the app actions of the menu bar list. The web view cannot open the
 * browser, open a window or quit by itself, so it asks PHP, and PHP asks NativePHP.
 */
final class AppActionController
{
    /**
     * Opens a job link in the default browser. Only http and https: a link comes from a source.
     */
    public function openUrl(Request $request, Shell $shell): Response
    {
        $url = $request->validate(['url' => ['required', 'string', 'url:http,https', 'max:2048']])['url'];

        $shell->openExternal($url);

        return response()->noContent();
    }

    /**
     * Opens the settings window. M8c builds its panes.
     */
    public function openSettings(WindowManager $windows): Response
    {
        $windows->open('settings')
            ->url(route('settings'))
            ->title('Sudo Hire Settings')
            ->width(720)
            ->height(520)
            ->minWidth(560)
            ->minHeight(420);

        return response()->noContent();
    }

    public function quit(App $app): Response
    {
        $app->quit();

        return response()->noContent();
    }
}
