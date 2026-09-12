<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EducationalTopicController;

Route::get('/education', [EducationalTopicController::class, 'index'])
    ->name('education.index');

Route::get('/education/{educationalTopic}', [EducationalTopicController::class, 'show'])
    ->name('education.show');
