<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Thank you page</title>

    <style>
      * {
        padding: 0;
        margin: 0;
      }
      button {
      }
      @media screen and (max-width: 488px) {
        .container {
          width: 90% !important;
        }
        h2 {
          font-size: 16px;
        }
      }
    </style>
  </head>
  <body
    style="
      height: 100vh;
      width: 100vw;
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: #cccccc;
    "
  >
    <div class="container" style="width: 40%; background: #fff; padding: 40px">
      <div
        style="
          display: flex;
          align-items: center;
          justify-content: space-around;
        "
      >
        <a
          href="#"
          style="
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            color: #2e2f32;
          "
          title="PsychInsights"
        >
          <img src="./img/logo.svg" alt="Psych-logo" />
          <h2>PsychInsights</h2>
        </a>
        <img src="./img/line.svg" alt="line" />
      </div>

      <div
        style="
          height: 35vh;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 25px;
        "
      >
        <img src="./img/checked.svg" alt="checked-icon" width="100px" />
        <div id="message">
        </div>
      </div>
    </div>
  </body>

  <script>
    const BASE_URL = "https://backend-dev.psychinsightsapp.com/api";
    const token = window.location.search.split("=")[1];

    async function verifyEmail(e) {
      // e.preverntDefault();
      const response = await fetch(`${BASE_URL}/verify-email`, {
        method: "POST",
        headers: {
          "Content-type": "application/json",
          "Accept": "application/json"
        },
        body: JSON.stringify({ token: token }),
      });
      const output = await response.json();
      console.log(output);

      console.log(response);
      if (response.ok) {
        var messageId = document.getElementById("message");
        var message = "<h3>Verified!</h3>";
        message += "<p>Thank you, you have successfully verified your account</p>";
        messageId.innerHTML = message;
      } else {
        var messageId = document.getElementById("message");
        var message = "<h3>Oopsie!</h3>";
        message += "<p>"+output.message+"</p>";
        messageId.innerHTML = message;
      }
    }

    verifyEmail();
  </script>
</html>
