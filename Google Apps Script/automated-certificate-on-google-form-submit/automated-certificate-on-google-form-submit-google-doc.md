1. **Trigger on Form Submission:** The script will activate when a new form response is submitted.
2. **Generate a Certificate:** Use a Google Slides or Docs template with placeholders for the student's name.
3. **Send the Certificate via Email:** Convert the filled template into a PDF and email it to the student.

Here is the Google Apps Script code you can use:

### Google Apps Script Code

1. **Create a Certificate Template:**
   - Create a Google Slides or Google Docs template with placeholders (e.g., `{{Name}}`).

2. **Apps Script:**
   - Go to Extensions > Apps Script in your Google Sheet.
   - Copy and paste the following script:

```javascript
function onFormSubmit(e) {
   // Get the submitted form data
   var formResponse = e.response;
  // If all questions are required, getItemResponses returns responses in form-order
  var itemResponses = formResponse.getItemResponses();
  var name = itemResponses[0].getResponse();  // returns a string
  var email = itemResponses[2].getResponse(); // returns a string

  // Generate certificate
  var certificateTemplateId = 'YOUR_TEMPLATE_ID'; // Google Slides/Docs template ID
  var folderId = 'YOUR_FOLDER_ID'; // Folder ID where certificates will be saved
  
  // Create a copy of the template and replace placeholders
  var template = DriveApp.getFileById(certificateTemplateId);
  var certificate = template.makeCopy('Certificate for ' + name, DriveApp.getFolderById(folderId));
  
  var doc = DocumentApp.openById(certificate.getId());
  var body = doc.getBody();
  body.replaceText('{{Name}}', name);
  doc.saveAndClose();
  
  // Convert certificate to PDF
  var pdf = DriveApp.getFileById(certificate.getId()).getAs('application/pdf');

  // Send email with certificate
  var subject = 'Your Certificate for AI Seminar';
  var message = 'Dear ' + name + ',\n\nThank you for attending our AI seminar. Please find your certificate attached.';
  
  MailApp.sendEmail({
    to: email,
    subject: subject,
    body: message,
    attachments: [pdf]
  });
  
  // Optionally, delete the Google Docs certificate if only the PDF is needed
  DriveApp.getFileById(certificate.getId()).setTrashed(true);
}
```

### Steps to Implement:

1. **Set Up the Trigger:**
   - In Apps Script, go to Triggers (clock icon) > Add Trigger.
   - Choose `onFormSubmit` for the function to run on the event of form submission.

2. **Replace IDs:**
   - Replace `YOUR_TEMPLATE_ID` with your Google Slides/Docs template ID.
   - Replace `YOUR_FOLDER_ID` with the folder ID where certificates will be stored.

This script will generate a personalized certificate and email it to the student after each form submission. Let me know if you need further assistance setting up the script or have additional requirements!