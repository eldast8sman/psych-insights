<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HTML Email</title>
    <style type="text/css">
      body {
        margin: 0;
        background-color: #cccccc;
      }
      table {
        border-spacing: 0;
      }
      td {
        padding: 0;
      }
      img {
        border: 0;
      }
      .wrapper {
        table-layout: fixed;
        background-color: #cccccc;
        padding-bottom: 40px;
      }
      .main {
        background-color: #ffffff;
        margin: 0 auto;
        width: 100%;
        max-width: 600px;
        border-spacing: 0;
        font-family: sans-serif;
        color: #4a4a4a;
      }
      @media screen and (max-width: 600px) {
      }
    </style>

    <script>
      const BASE_URL = "https://backend-dev.psychinsightsapp.com/api";
      const token = window.location.search.split("=")[1];

      async function verifyEmail(e) {
        // e.preverntDefault();
        const response = await fetch(`${BASE_URL}/verify-email`, {
          method: "POST",
          headers: {
            "Content-type": "application/json",
          },
          body: JSON.stringify({ token: token }),
        });
        const output = await response.json();
        console.log(output);
        if (response.ok) {
          window.location.replace("./thank-you.html");
        } else {
          window.location.replace("./error.html");
        }
      }

      const verifyBtn = document.querySelector(".verify-btn");

      verifyBtn.addEventListener("click", verifyEmail);
    </script>
  </head>

  <body>
    <center class="wrapper">
      <table class="main" width="100%" style="padding: 30px">
        <tr>
          <td>
            <table width="100%">
              <tr>
                <td>
                  <a
                    href="https://psychinsightsapp.com/"
                    style="
                      display: flex;
                      align-items: center;
                      justify-content: center;
                      gap: 5px;
                      text-decoration: none;
                      color: #2e2f32;
                    "
                    title="PsychInsights"
                  >
                    <img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/logo+(1).png"
                      alt="Psych-logo"
                    />
                    <span>PsychInsights</span>
                  </a>
                </td>
              </tr>
            </table>
          </td>
        </tr>

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
                    Forgot password?
                  </p>
                  <p
                    style="
                      text-align: center;
                      color: #000000;
                      margin-top: -20px;
                      font-size: 14px;
                    "
                  >
                    Reset Your Psych Insights Password
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

        <tr>
          <td>
            <table width="90%" style="margin-top: 20px">
              <tr>
                <td style="text-align: center">
                  <p style="text-align: center">
                    Not you? Contact
                    <a href="" style="color: #207384">Support</a>
                  </p>
                  <p style="text-align: center">
                    Want to know more about terms of use?<a
                      href=""
                      style="color: #207384"
                      >Click here</a
                    >
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td>
            <table width="90%" style="margin: auto">
              <tr>
                <td
                  style="
                    background-color: #f0f1f9;
                    border-radius: 10px;
                    padding: 5px 0px;
                    display: flex;
                    gap: 10px;
                    align-items: center;
                    justify-content: center;
                    text-align: center;
                  "
                >
                  <a
                    href="https://www.tiktok.com/@_psychinsights_?_t=8igxYC9rlr8&_r=1"
                    class="tiktolk"
                    style="text-align: center"
                    ><img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/logos_tiktok-icon.png"
                      style="text-align: center"
                  /></a>
                  <a
                    href="https://www.instagram.com/_psych_insights_?igsh=aXd1bHB0eWJvYTBy"
                    class="instagram"
                    style="text-align: center"
                    ><img
                      src="https://psychinsight-email-icons.s3.us-east-2.amazonaws.com/intagram%2C.png"
                      style="text-align: center"
                  /></a>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </center>
  </body>
</html>
