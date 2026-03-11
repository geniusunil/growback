<?php

use Illuminate\Support\Facades\Route;



Route::get('/google-test', function () {
    return view('google-test');
});

Route::get('/create-task', function () {
    return view('task');
});
