@extends('emails.layouts.newapp')

@section('content')
<tr>
  <td>
    <table width="100%">
      <tr>
        <td>
          <p
            style="
              text-align: center;
              font-size: 40px;
              font-weight: 500;
              color: #000000;
            "
          >
            Verify your email
          </p>
          <p
            style="
              text-align: center;
              color: #000000;
              margin-top: -20px;
              font-size: 14px;
            "
          >
            You’re almost there! Just confirm your email
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
        <td style="font-size: 14px">
          <p style="margin-top: 30px">
            Hello <span>{{ $name }}</span>,
          </p>
          <p style="margin-bottom: 20px; text-align: center">
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
          <p style="text-align: center">Your code:</p>
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