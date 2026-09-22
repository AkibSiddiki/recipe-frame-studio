<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;
use Native\Desktop\Menu\Items\Label;
use Native\Desktop\Menu\Items\Separator;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Menu::create(
            Menu::app(),
            Menu::create()
                ->label('File')
                ->submenu([
                    Label::make('New Project')
                        ->accelerator('CmdOrCtrl+N')
                        ->url(route('project.create')),
                    new Separator,
                    Label::make('Settings')
                        ->accelerator('CmdOrCtrl+,')
                        ->url(route('settings')),
                    new Separator,
                    Label::make('Quit')
                        ->accelerator('CmdOrCtrl+Q')
                        ->quit(),
                ]),
            Menu::edit(),
            Menu::view(),
            Menu::window(),
        );

        Window::open()
            ->width(1280)
            ->height(800)
            ->minWidth(1024)
            ->minHeight(700)
            ->title('Recipe Frame Studio')
            ->route('home')
            ->resizable();
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'max_execution_time' => '300',
        ];
    }
}
