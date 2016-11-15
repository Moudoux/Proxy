<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	   
		<meta name="description" content="Admin docs for OpenTexon staff">
		<meta name="author" content="OpenTexon">
		<link rel="icon" href="favicon.ico">

		<title>SkyNet Proxy</title>

		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<style>
			/* Sticky footer styles */
			html {
			  position: relative;
			  min-height: 100%;
			}
			body {
			  /* Margin bottom by footer height */
			  margin-bottom: 60px;
			}
			.footer {
			  position: absolute;
			  bottom: 0;
			  /* Set the fixed height of the footer here */
			  height: 60px;
			}
		</style>
		
		<style>
			.contactBackground{
				background-color:#FFF;
				padding:20px;
				box-shadow: 0 0 20px lightgray;
			}
		</style>
		
	</head>
	<body>

		<div id="_skynet_navbar_"></div>
		<br><br><br>
		<div class="container">
			<div class="jumbotron contactBackground">
	  
				<div style="padding-bottom: 9px; margin: 40px 0 20px; border-bottom: 1px solid #3e1c1c;">
					<h2>SkyNet Proxy</h2>
				</div>

			
					<div class="input-group">
					   <input type="text" id="q" placeholder="Enter any url..." class="form-control">
					   <span class="input-group-btn">
							<button onClick="load_page();" class="btn btn-default" id="enter" type="button">Go!</button>
					   </span>
					</div>
					
					<br>

					<font size="3">
						All external resources such as CSS files, images and more will be loaded through the proxy. All JavaScript will be removed to prevent any tracking.
						The url will be encoded to hide what you are visiting, the page title and icon will also be hidden.<br><br>
						Supported websites:<br>
						All clearnet domains (.com,.net, etc)<br>
						Hidden service domains (.onion aka TOR)<br>
					</font>

					<br><br>
			</div>
		</div>

		<footer class="footer" style="height: 30px;">
			<div class="container">
				<p class="text-muted">Copyright &copy; OpenTexon <?php echo date('Y'); ?> All Rights Reserved | Made in Sweden</p>
			</div>
		</footer>

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
	
		<script>
		
			function load_page() {
				var file = "https://api.sky-net.me/proxy/proxy?u=" + btoa(document.getElementById("q").value);
				window.location = file;
			}
			
			$("#q").keyup(function(event){
				if(event.keyCode == 13){
					$("#enter").click();
				}
			});
		
		</script>
		
	</body>
</html>