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
          Your Premium Access is Confirmed!
          </p>
          <p
            style="
              text-align: center;
              color: #000000;
              margin-top: -20px;
              font-size: 14px;
            "
          >
          Here's What Happens Next
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
            Dear <span>{{ $name }}</span>,
          </p>
          <p style="margin-bottom: 20px; text-align: left">
            Thank you for upgrading to the Premium version of Psych Insights!
          </p>
          <p style="margin-bottom: 20px; text-align: left">
            <strong>Here's What You Need to Know:</strong>
            <ul>
                <li>
                    <strong>Premium Activation After Free Trial:</strong>
                    Since you upgraded during your 7-day free trial, your Premium access will officially begin once your trial period ends. Until then, you can continue exploring the free trial features.
                </li>
                <li>
                    <strong>More In-Depth Questionnaire:</strong>
                    Once your Premium access is activated, you'll be able to complete our comprehensive questionnaire, which is designed to provide even deeper insights tailored to your needs.
                </li>
            </ul>
          </p>
          <p style="margin-bottom: 20px; text-align: left">
            We appreciate your trust in Psych Insights and can't wait for you to experience everything the Premium version has to offer. If you have any questions or need further assistance, feel free to reach out.
            <br />
            Thank you for choosing Psych Insights to support your journey.
          </p>
          <p style="margin-bottom: 20px; text-align: left">
            Warm regards,
            <br />
            The Psych Insights Team
          </p>
        </td>
      </tr>
    </table>
  </td>
</tr>
@endsection
