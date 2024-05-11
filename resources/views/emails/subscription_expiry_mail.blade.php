@extends('emails.layouts.app')

@section('title')
  Subscription Expiry
@endsection

@section('subtitle')
  
@endsection

@section('content')

<tr> 
  <td style="font-size: 14px"> 
    <p style="margin: 40px 0px">Hello {{ $name }},</p>
    <p style="margin-bottom: 40px">
      Hello! We've noticed your Psych Insights Premium Subscription has expired. 
      <br />
      To keep enjoying all the great resources and features available through Psych Insights, simply renew your subscription by upgrading to our Premium Plan.
      Don’t let your progress stall—continue your journey with us today! 
    </p>
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
            >Dashboard</a
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
