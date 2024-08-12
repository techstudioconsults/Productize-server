<?php

namespace App\Dtos;


/**
 * @author @obajide028 Odesanya Babajide
 *
 * @version 1.0
 *
 * @since 12-12-2024
 *
 * Data Transfer Object for the updated admin details
 * 
 */

class AdminUpdateDto
 {
   public $full_name;
   public $email;
   public $password;


   public function __construct(string $full_name, string $email, ?string $password = null)
   {
       $this->full_name = $full_name;
       $this->email = $email;
       $this->password = $password;
   }
 }