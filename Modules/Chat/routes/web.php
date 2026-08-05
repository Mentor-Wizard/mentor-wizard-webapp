<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Chat\Actions\ChatListUser;
use Modules\Chat\Actions\ChatMessages;
use Modules\Chat\Actions\CreateChat;
use Modules\Chat\Actions\DownloadChatFile;
use Modules\Chat\Actions\GetMessage;
use Modules\Chat\Actions\Pages\GetChatPage;
use Modules\Chat\Actions\SendMessage;
use Modules\Chat\Actions\SetArchive;
use Modules\Chat\Actions\SetBan;
use Modules\Chat\Actions\SetMute;

Route::middleware('auth')
    ->prefix('chat')
    ->group(function (): void {
        Route::get('list', GetChatPage::class)->name('page.chat');
        Route::get('users', ChatListUser::class)->name('chat.users');
        Route::get('messages/{chat}', ChatMessages::class)->name('chat.messages')->can('view,chat');
        Route::post('message/{chat}', SendMessage::class)->middleware('throttle:chat-send')->name('chat.send-message')->can('update,chat');
        Route::get('message/{message}', GetMessage::class)->name('chat.get-message')->can('view,message');
        Route::get('message/{message}/download/{media}', DownloadChatFile::class)->can('view,message')->name('chat.message.download');
        Route::post('mute/{chat}', SetMute::class)->middleware('throttle:chat-send')->name('chat.set-mute')->can('update,chat');
        Route::post('create/{user}', CreateChat::class)->middleware('throttle:chat-create')->name('chat.create');
        Route::post('archive/{chat}', SetArchive::class)->middleware('throttle:chat-send')->name('chat.set-archive')->can('update,chat');
        Route::post('ban/{chat}', SetBan::class)->middleware('throttle:chat-send')->name('chat.set-ban')->can('update,chat');
    });
