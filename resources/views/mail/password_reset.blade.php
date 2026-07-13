<!-- resources/views/emails/reset_full.blade.php -->

@extends('layouts.mail') <!-- You can use a layout if needed -->

@section('content')
   <table class="table" style="width: 100%;">
       <tr>
           <td class="p-4 text-center bg-white">
               <img src="{{ asset('logo.png') }}" alt="Your Logo" class="img-fluid" style="max-width: 50%;">
           </td>
       </tr>
       <tr>
           <td class="p-4 bg-white text-center"> <!-- Added text-center class -->
               <h3 class="mb-4 text-center">Hi Bapak/Ibu {{ $profileName }},</h3>
               <p class="text-center">Kami telah menerima permohonan anda untuk melakukan reset password.</p>
               <button href="{{ $resetUrl }}" class="btn btn-secondary">Reset Password</button> <!-- Used btn-secondary class -->
               <p class="mt-3 text-center">Link reset password ini akan kadaluarsa dalam {{ $expireTime }} menit.</p>
               <p class="text-center">Jika Anda tidak melakukan permohonan untuk melakukan reset password, mohon abaikan email ini.</p>
           </td>
       </tr>
       <tr>
           <td class="p-4 text-center bg-light">
               <p>Jika terdapat kendala saat mengklik tombol, anda dapat mengklik atau menyalin link berikut ini: 
                <a target="_blank" rel="noopener noreferrer" href="{{ $resetUrl }}" >{{ $resetUrl }}</a>
               </p>
           </td>
       </tr>
       <tr>
           <td class="p-4 text-center bg-dark text-white">
               &copy; {{ date('Y') }} PT WahanaArtha Ritelindo
           </td>
       </tr>
   </table>
@endsection
