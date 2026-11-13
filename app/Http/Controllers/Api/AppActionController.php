<?php

namespace App\Http\Controllers\Api;

use App\List\DiagnosticReport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Native\Desktop\App;
use Native\Desktop\Client\Client;
use Native\Desktop\Clipboard;
use Native\Desktop\MenuBar\MenuBarManager;
use Native\Desktop\Notification;
use Native\Desktop\Shell;
use Native\Desktop\Windows\Window;
use Native\Desktop\Windows\WindowManager;
use Throwable;

/**
 * POST /api/app/*: the app actions of the menu bar list, the settings window and the first run. The web view
 * cannot open the browser, Finder or a window, use the clipboard or quit by itself, so it asks
 * PHP, and PHP asks NativePHP. Paths come from PHP, never from the request.
 */
final class AppActionController
{
    public const string TEST_NOTIFICATION = 'Notifications work. Sudo Hire sends one summary when new jobs match.';

    public const string TEST_REFERENCE = 'sudo-hire-test';

    /** The settings window title for each pane (DESIGN.md "Settings window"). */
    public const array PANE_TITLES = [
        'where' => 'Where you work',
        'jobs' => 'Jobs',
        'pay' => 'Pay',
        'notifications' => 'Notifications',
        'sources' => 'Sources',
        'companies' => 'Companies',
        'privacy' => 'Privacy',
        'troubleshooting' => 'Troubleshooting',
    ];

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
     * Opens the settings window, on a pane when the request names one ("Details" opens Sources).
     * Opening a window that is open only brings it to the front, so an open window gets the new URL.
     * NativePHP opens the developer tools in debug mode, so turn them off.
     */
    public function openSettings(Request $request, WindowManager $windows, Client $client): Response
    {
        $pane = $request->validate(['pane' => ['sometimes', 'string', Rule::in(array_keys(self::PANE_TITLES))]])['pane'] ?? null;
        $url = $pane === null ? route('settings') : route('settings', ['pane' => $pane]);

        if ($pane !== null && $this->isOpen($windows, 'settings')) {
            (new Window('settings'))->setClient($client)->url($url);
        }

        $windows->open('settings')
            ->url($url)
            ->title('Sudo Hire Settings')
            ->width(720)
            ->height(520)
            ->minWidth(560)
            ->minHeight(420)
            ->showDevTools(false);

        return response()->noContent();
    }

    /**
     * "Open the list" on the last screen of the first run.
     */
    public function openList(WindowManager $windows, MenuBarManager $menuBar): Response
    {
        $windows->close('first-run');
        $menuBar->show();

        return response()->noContent();
    }

    /**
     * "Send a test notification" on the last screen of the first run. The first notification makes macOS ask
     * if Sudo Hire can show notifications, so the user sees that question while the first run is open.
     * 502 when the internal API does not show it.
     */
    public function testNotification(Notification $notification): Response
    {
        try {
            $notification
                ->title('Sudo Hire')
                ->message(self::TEST_NOTIFICATION)
                ->reference(self::TEST_REFERENCE)
                ->show();
        } catch (Throwable $error) {
            report($error);

            return response()->noContent(502);
        }

        // The internal API gives the reference back only when it shows the notification.
        return response()->noContent($notification->reference === self::TEST_REFERENCE ? 204 : 502);
    }

    public function quit(App $app): Response
    {
        $app->quit();

        return response()->noContent();
    }

    /**
     * Shows the database file in Finder, in its data folder.
     */
    public function showDataFolder(Shell $shell): Response
    {
        $shell->showInFolder((string) config('database.connections.'.config('database.default').'.database'));

        return response()->noContent();
    }

    public function openLogFolder(Shell $shell): Response
    {
        $shell->openFile(storage_path('logs'));

        return response()->noContent();
    }

    public function copyDiagnostics(DiagnosticReport $report, Clipboard $clipboard): Response
    {
        $clipboard->text($report->text());

        return response()->noContent();
    }

    /**
     * The window title follows the selected pane. Electron ignores the title of the page.
     */
    public function settingsTitle(Request $request, Client $client): Response
    {
        $pane = $request->validate(['pane' => ['required', 'string', Rule::in(array_keys(self::PANE_TITLES))]])['pane'];

        (new Window('settings'))->setClient($client)->title(self::PANE_TITLES[$pane]);

        return response()->noContent();
    }

    private function isOpen(WindowManager $windows, string $id): bool
    {
        foreach ($windows->all() as $window) {
            if ($window->getId() === $id) {
                return true;
            }
        }

        return false;
    }
}
