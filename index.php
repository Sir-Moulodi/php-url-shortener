<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP URL Shortener</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; max-width: 500px; width: 100%; }
        h1 { color: #333; }
        input[type="url"] { width: 80%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        button { padding: 10px 20px; background-color: #5c67f2; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; margin-top: 1rem; }
        button:hover { background-color: #4a54e1; }
        #result { margin-top: 1.5rem; font-size: 16px; word-wrap: break-word; }
        #result a { color: #5c67f2; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Link Shortener Pro</h1>
        <form id="shorten-form">
            <input type="url" id="long_url" name="long_url" placeholder="Enter a long URL here..." required>
            <br>
            <button type="submit">Shorten!</button>
        </form>
        <div id="result"></div>
    </div>

    <script>
        document.getElementById('shorten-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const longUrl = document.getElementById('long_url').value;
            const resultDiv = document.getElementById('result');

            try {
                const response = await fetch('shorten.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `long_url=${encodeURIComponent(longUrl)}`
                });
                
                const data = await response.json();

                if (data.success) {
                    const shortUrl = `${window.location.protocol}//${window.location.host}${window.location.pathname.replace('index.php', '')}${data.short_code}`;
                    resultDiv.innerHTML = `Short URL: <a href="${shortUrl}" target="_blank">${shortUrl}</a>`;
                } else {
                    resultDiv.innerHTML = `<span style="color: red;">Error: ${data.message}</span>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<span style="color: red;">An unexpected error occurred.</span>`;
            }
        });
    </script>
</body>
</html>