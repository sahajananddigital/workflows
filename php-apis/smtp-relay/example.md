```
function sendEmailWithDriveAttachment() {
  const RELAY_URL = "https://your-domain.com/relay.php";
  const AUTH_TOKEN = "generate_a_long_random_secret_token_here";

  // 1. Fetch file from Google Drive (or construct a blob)
  const fileId = "1AbCdEfGhIjKlMnOpQrStUvWxYz"; // Replace with your Drive file ID
  const file = DriveApp.getFileById(fileId);
  const blob = file.getBlob();

  // 2. Prepare payload with attachment array
  const payload = {
    from: "billing@yourdomain.com",
    from_name: "Billing Department",
    to: "client@example.com",
    subject: "Your Monthly Statement",
    html: "<p>Please find your requested document attached.</p>",
    text: "Please find your requested document attached.",
    attachments: [
      {
        filename: file.getName(),
        mime_type: blob.getContentType(),
        base64: Utilities.base64Encode(blob.getBytes())
      }
    ]
  };

  // 3. Dispatch to PHP Relay
  const options = {
    method: "post",
    contentType: "application/json",
    headers: {
      "Authorization": "Bearer " + AUTH_TOKEN
    },
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  };

  const response = UrlFetchApp.fetch(RELAY_URL, options);
  Logger.log(response.getContentText());
}
```