<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WebSocket Test</title>
    @vite(['resources/js/echo.js'])
</head>
<body>
    <h1>WebSocket Test</h1>
    <div id="status">Loading WebSocket...</div>
    <div id="messages"></div>

    <script>
        setTimeout(function() {
            if (typeof window.Echo !== 'undefined') {
                window.Echo.channel('A')
                    .listen('TaskUpdated', (event) => {
                        document.getElementById('status').innerText = 'Event received!';
                        document.getElementById('messages').innerText = JSON.stringify(event, null, 2);
                    })
                    .error((error) => {
                        document.getElementById('status').innerText = 'Error connecting to WebSocket.';
                    });
            } else {
                document.getElementById('status').innerText = 'Echo is not initialized. Try again later.';
            }
        }, 2000); 
    </script>
</body>
</html>
