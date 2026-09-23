<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Menu::create();

        Window::open()
            ->width(1280)
            ->height(800)
            ->minWidth(1024)
            ->minHeight(700)
            ->title('Recipe Frame Studio — রান্নাঘরের ডায়েরি')
            ->route('home')
            ->resizable()
            ->hideMenu();
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '1024M',
            'max_execution_time' => '600',
            'upload_max_filesize' => '2048M',
            'post_max_size' => '2048M',
        ];
    }
}
