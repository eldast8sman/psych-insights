@extends('emails.layouts.newapp')

@section('title')
<p style="font-size: 40px; font-weight: 500">
  Forgot password?
</p>
<img
  src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/padlock.svg"
  alt="padlock-icon"
  width="50px"
/>
@endsection

@section('subtitle')
  Reset Your Psych Insights Password
@endsection

@section('content')
<tr>
  <td>
    <table width="100%">
      <tr>
        <td style="font-size: 14px">
          <p style="margin: 30px 0px">
            Hello <span>{{ $name }}</span>,
          </p>
          <p style="margin-bottom: 40px; text-align: center">
            To set up a new password to your Psych Insights account,
            enter this code on your device.
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
          <p>Your code:</p>
          <span
            style="
              color: #ffffff;
              padding: 15px 55px;
              background: #207384;
              border-radius: 10px;
              text-decoration: none;
              letter-spacing: 10px;
              font-size: 25px;
              font-weight: bold;
            "
          >
            {{ $token }}
          </span>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection