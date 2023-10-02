@extends('emails.layouts.app')

@section('content')
<tr>
  <td>
    <table width="100%">
      <tr>
        <td>
          <div
            style="
              display: flex;
              text-align: center;
              align-items: center;
              gap: 10px;
              justify-content: center;
              color: #000000;
            "
          >
            <p
              style="
                font-size: 40px;
                font-weight: 500;
                text-align: center;
              "
            >
              Activate Your Account
            </p>

            <img
              src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/checked.svg"
              alt="checked-icon"
              width="50px"
            />
          </div>
          <p
            style="
              text-align: center;
              color: #000000;
              margin-top: -30px;
              font-size: 14px;
            "
          >
            Please activate your PsychInsights Admin Account
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
          <p style="margin: 40px 0px">Hello {{ $name }},</p>
          <p style="margin-bottom: 40px">
            You have been added as an Admin on the PsychInsights App.
            Please click on the link below to activate your account.
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
            href="{{ $link }}"
            style="
              color: #ffffff;
              padding: 15px 55px;
              background: #207384;
              border-radius: 30px;
              text-decoration: none;
            "
            >Activate Account</a
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
            Warm regards, <br />
            The PsychInsights Team
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection