<?php
    require_once('vendor/autoload.php');
	include_once "php/sessionchecker.php";
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>  </title>
<link href="plugins/bootstrap-5.0.2/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />
<link rel="stylesheet" href="../../plugins/sweet-alert-2/sweetalert2.min.css">
<script src="plugins/sweet-alert-2/sweetalert2.all.min.js"></script>

<style>
	 .bg {
            background-image: url("index.jpg");
            background-repeat: no-repeat;
            background-size: cover;
        }

		#container {
            max-width: 500px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-image: linear-gradient(to right, rgba(0,0,0,0.5), rgba(0,0,0,0.8));
        }

		form i {
            /*margin-left: -30px;*/
            cursor: pointer;
        }
</style>

</head>
<body class="bg">
		
        <div class="vh-100 d-flex justify-content-center align-items-center">
            <div id="container" class="col-md-5 p-5 shadow-lg border rounded border-warning">
                <h1 class="text-center mb-4 text-warning font-weight-bolder mb-5">S E O</h2>
				<div class="d-grid">
					<button class="btn btn-primary btn-lg" id="btnOAuthSignIn">Sign in with Google</button>
				</div>
            </div>
        </div>
    

<script src="plugins/bootstrap-5.0.2/js/bootstrap.bundle.min.js"></script>
<script src="plugins/jquery/jquery-3.7.1.min.js"></script>
<script type="text/javascript">

	var token = "";

	const redirectUri = 'https://www.ebac-service.com';
	const clientId = '1009430611512-ff7bchq55gh01lj688bl9pkt2u0uq0ou.apps.googleusercontent.com';
	const scopes = "email profile https://www.googleapis.com/auth/spreadsheets";


	ShowErrorMessage = ()=>{
		Swal.fire({
			icon: 'error',
			title: 'Oops...',
			text: 'Invalid Credentials!'
		})
	}


	$('#btnOAuthSignIn').on('click', () => {

		

		const authUrl = `https://accounts.google.com/o/oauth2/auth?redirect_uri=${redirectUri}
		&response_type=token&client_id=${clientId}&scope=${scopes}`;

		window.location.href = authUrl;
	});

	const handleOAuthCallback = () => {
	const params = new URLSearchParams(window.location.hash.substr(1));
		if (params.has('access_token')) {
			const accessToken = params.get('access_token');
			token = accessToken;
			getUserInfo(accessToken);
		}
	};

	const getUserInfo = accessToken => {
    fetch('https://www.googleapis.com/oauth2/v2/userinfo', {
      headers: {
        'Authorization': `Bearer ${accessToken}`
      }
    })
    .then(response => response.json())
		.then(data => {

			data.accessToken = token;

			$.ajax({
				url: "php/oauth.php",
				type: "POST",
				dataType: "json",
				data: {
					data : JSON.stringify(data)
				},
				success: function(data){
					if(data.success){
						location.reload();
					}
					else{
						ShowErrorMessage();	
					}
				},
				error: function(xhr, textStatus, errorThrown) {
					console.error('Error:', errorThrown);
				}
			});
			
			}).catch(error => console.error('Error fetching user info:', error));
		};
	
	$(document).ready(()=>{
		handleOAuthCallback();
	})

</script>

</body>
</html>