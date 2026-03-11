<!DOCTYPE html>
<html>
<head>
    <title>Google Login API Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<h2>Google Login Test (API)</h2>

<div id="g_id_onload"
     data-client_id="{{ env('GOOGLE_CLIENT_ID') }}"
     data-callback="handleCredentialResponse">
</div>

<div class="g_id_signin"></div>

<pre id="result"></pre>

<script src="https://accounts.google.com/gsi/client" async defer></script>


<script>
function handleCredentialResponse(response) {

   console.log("Google ID Token:", response.credential);


    fetch("/api/google-login", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            id_token: response.credential
        })
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById("result").innerText =
            JSON.stringify(data, null, 2);
    })
    .catch(err => console.error(err));
}
</script>

</body>
</html>