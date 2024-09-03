<?php

namespace App\Models;

use Illuminate\Support\Facades\App;
use App\Classes\NamecheapWrapper\Contracts\ApiWrapperFactoryServiceInterface;
use Dotenv\Exception\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;

class NamecheapAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'api_key',
        'email',
    ];

}
