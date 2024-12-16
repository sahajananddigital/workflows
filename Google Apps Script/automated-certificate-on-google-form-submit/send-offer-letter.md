You can automate the process of generating and sending offer letters using Google Apps Script by following these steps:

### Steps to Automate Offer Letter Distribution
1. **Prepare a Google Sheet**:
   - Create columns for `Name`, `Email`, and any other required data (e.g., position, start date).
   
2. **Create a Google Doc Template**:
   - Design your offer letter in Google Docs.
   - Use placeholders (e.g., `{{Name}}`, `{{Position}}`) for dynamic content.

3. **Write the Apps Script**:
   - The script will:
     1. Read data from the Google Sheet.
     2. Replace placeholders in the template with actual data.
     3. Generate a PDF for each offer letter.
     4. Email the PDF to each intern.

4. **Deploy and Run**:
   - Trigger the script manually or set up a time-based trigger.

### Sample Google Apps Script Code
Here’s an example to get you started:

```javascript
function sendOfferLetters() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('Interns'); // Sheet name
  const templateDocId = 'YOUR_TEMPLATE_DOC_ID'; // Replace with your template ID
  const subject = 'Your Internship Offer Letter';
  const emailBody = 'Dear {{Name}},\n\nPlease find your offer letter attached.\n\nBest regards,\n[Your Company]';

  // Get all data from the sheet
  const data = sheet.getDataRange().getValues();
  const headers = data.shift(); // Remove and get the header row

  // Map data for easier access
  data.forEach(row => {
    const record = headers.reduce((obj, header, index) => {
      obj[header] = row[index];
      return obj;
    }, {});

    // Create a new document from the template
    const doc = DriveApp.getFileById(templateDocId).makeCopy().getId();
    const docFile = DocumentApp.openById(doc);
    const body = docFile.getBody();

    // Replace placeholders with actual data
    Object.keys(record).forEach(key => {
      body.replaceText(`{{${key}}}`, record[key]);
    });
    docFile.saveAndClose();

    // Convert the document to PDF
    const pdf = DriveApp.getFileById(doc).getAs('application/pdf');
    DriveApp.getFileById(doc).setTrashed(true); // Delete the temporary doc

    // Send email with the PDF attachment
    GmailApp.sendEmail(record.Email, subject, emailBody.replace('{{Name}}', record.Name), {
      attachments: [pdf]
    });
  });
}
```

### Notes:
- Replace `YOUR_TEMPLATE_DOC_ID` with the actual Google Doc template ID.
- Ensure your placeholders in the template match the column headers in the sheet.

### How to Use
1. **Adjust the Range**:
   - Modify `startRow` and `endRow` to set the range of rows you want to process.

2. **Test the Script**:
   - Run the script for a small range first to ensure it works as expected.

3. **Notes**:
   - The range is inclusive, so `startRow` to `endRow` will include all rows within this range.
   - Ensure the column names in your Google Sheet match the placeholders in the template.

Let me know if you'd like further adjustments!