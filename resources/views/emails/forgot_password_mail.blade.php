@extends('emails.layouts.app')

@section('title')
  Reset Your Password
@endsection

@section('subtitle')
  Please reset your Password to access your Account
@endsection

@section('content')
<tr>
  <td>
    <table width="100%">
      <tr>
        <td style="font-size: 14px">
          <p style="margin: 40px 0px">Hello {{ $name }},</p>
          <p style="margin-bottom: 40px">
            We received a request to reset the password for your PsychInsights account. If you initiated this request, please click on the link below to set a new password:
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>

<tr>
  <td>
    <table width="100%">
      <tr>
        <td style="text-align: center">
          <a
            href="{{ env('FRONTEND_URL') }}/?forgetToken={{ $token }}"
            style="
              color: #ffffff;
              padding: 15px 55px;
              background: #207384;
              border-radius: 30px;
              text-decoration: none;
            "
            >Reset Password</a
          >
        </td>
      </tr>
    </table>
  </td>
</tr>

<tr>
  <td>
    <table width="100%">
      <tr>
        <td style="font-size: 14px">
          <p style="margin-top: 40px">
            If you didn't request a password reset, please disregard this email. It's a good practice to periodically update your password to ensure your account's security.
            <br>
            Remember, PsychInsights will never ask you for your password through email or any other unsolicited communication. Always ensure you're communicating directly with our community.
          </p>
          <p>
            Warm regards, <br />
            The PsychInsights Team
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection