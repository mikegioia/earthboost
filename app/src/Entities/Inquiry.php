<?php

namespace App\Entities;

use App\Entity
  , App\Models\Inquiry as InquirtModel;

class Inquiry extends Entity
{
    public $id;
    public $name;
    public $email;
    public $company;

    protected $_modelClass = 'Inquiry';
}