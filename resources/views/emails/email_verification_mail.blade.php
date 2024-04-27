@extends('emails.layouts.newapp')

@section('title')
  <p
  style="
    font-size: 40px;
    font-weight: 500;
    text-align: center;
  "
  >
  Verify your email
  </p>

  <img
    src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/white-instagram.png"
    alt="checked-icon"
    width="50px"
  />
@endsection

@section('subtitle')
  Please verify Email to secure Account
@endsection

@section('content')
<tr>
  <td>
    <table width="100%">
      <tr>
        <td style="font-size: 14px">
          <h1 style="text-align: center; color: #000000">
            You’re almost there! <br />
            Just confirm your email
          </h1>
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
          <p style="margin: 40px 0px">
            Hello <span>{{ $name }}</span>,
          </p>
          <p style="margin-top: -20px">
            You’ve successfully created a Psych Insights account. To
            activate your account please enter this code.
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
              margin-top: -10px;
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