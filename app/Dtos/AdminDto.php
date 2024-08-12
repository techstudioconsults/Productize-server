<?php

namespace App\Dtos;


/**
 * @author @obajide028 Odesanya Babajide
 *
 * @version 1.0
 *
 * @since 12-12-2024
 *
 * Data Transfer Object for the admin details
 * 
 */

class AdminDto
 {
   public $email;
   public $password;


   public function __construct(string $email, ?string $password = null)
   {
       $this->email = $email;
       $this->password = $password;
   }
 }