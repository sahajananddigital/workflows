`customfunction.gs`
```JavaScript
function CERTIFICATE_QR(name, certificateid, start, end, department ) {  
  const studentname = encodeURIComponent(name);
  const purpose = encodeURIComponent("Inernship");
  const departmentType = encodeURIComponent(department)
  const startDate = encodeURIComponent(start);
  const endDate = encodeURIComponent(end);
  const certificateId = encodeURIComponent(certificateid);
  const textLink = encodeURIComponent(`https://certificate.sahajananddigital.in/?name=${studentname}&purpose=${purpose}&department=${departmentType}&start=${startDate}&end=${endDate}&certificate_id=${certificateId}`)
  const qrMessage = `https://api.qrserver.com/v1/create-qr-code/?size=200X200&data=${textLink}`;

  return qrMessage
}
```

`sendcertificate.gs`
```JavaScript

let slideTemplateId = "IDfasdfsadfasdfasdfsdfsdfads";
let tempFolderId = "fdferewvewrewredghr"; // Create an empty folder in Google Drive

/**
 * Creates a custom menu "Appreciation" in the spreadsheet
 * with drop-down options to create and send certificates
 */
function onOpen(e) {
  let ui = SpreadsheetApp.getUi();
  ui.createMenu('Appreciation')
  .addItem('Create certificates', 'createCertificates')
  .addSeparator()
  .addItem('Send certificates', 'sendCertificates')
  .addToUi();
}

/**
 * Creates a personalized certificate for each employee
 * and stores every individual Slides doc on Google Drive
 */
function createCertificates() {
  
  // Load the Google Slide template file
  let template = DriveApp.getFileById(slideTemplateId);
  
  // Get all employee data from the spreadsheet and identify the headers
  let sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  let values = sheet.getDataRange().getValues();
  let headers = values[0];
  let empNameIndex = headers.indexOf("Name");
  let startDateIndex = headers.indexOf("Start Date");
  let endDateIndex = headers.indexOf("End Date");
  let whichInternshipIndex = headers.indexOf("Which Internship");
  let certificateIndex = headers.indexOf("Certificate");
  let emailIndex = headers.indexOf("Email");
  let qrCodeLinkIndex = headers.indexOf("QR");
  
  let startRow = 23;
  let endRow = 24;

  // Iterate through each row to capture individual details
  for(let i = (startRow - 1); i < (endRow); i++) {
    let rowData = values[i];
    let empName = rowData[empNameIndex];
    let startDate = rowData[startDateIndex];
    let endDate = rowData[endDateIndex];
    let whichInternship = rowData[whichInternshipIndex];
    let certificate = rowData[certificateIndex];
    let qrcodeLink = rowData[qrCodeLinkIndex];
    let email = rowData[emailIndex];
    // Make a copy of the Slide template and rename it with employee name
    let tempFolder = DriveApp.getFolderById(tempFolderId);
    let empSlideId = template.makeCopy(tempFolder).setName(empName).getId();   
    let empMainSlide = SlidesApp.openById(empSlideId);
    let empSlide = empMainSlide.getSlides()[0];
    
    // Replace placeholder values with actual employee related details
    empSlide.replaceAllText("{{Name}}", empName);
    empSlide.replaceAllText("{{StartDate}}", "From " + Utilities.formatDate(startDate, Session.getScriptTimeZone(), "MMMM dd, yyyy"));
    empSlide.replaceAllText("{{EndDate}}", "Till " + Utilities.formatDate(endDate, Session.getScriptTimeZone(), "MMMM dd, yyyy"));
    empSlide.replaceAllText("{{Department}}", whichInternship);
    empSlide.replaceAllText("{{CertificateID}}", certificate);


    // Get all shapes on the slide
    const shapes = empSlide.getShapes();

    // Find the shape containing "{{QR_CODE}}"
    let qrCodeShape = null;
    for (let i = 0; i < shapes.length; i++) {
      const shape = shapes[i];
      if (shape.getShapeType() === SlidesApp.ShapeType.TEXT_BOX && shape.getText().asString().indexOf("{{QR_CODE}}") !== -1) {
        qrCodeShape = shape;
        console.log(shape.getText().asString());
        break;
      }
    }

    // If the shape is found, replace it with the image from the sheet
    if (qrCodeShape) {
      Logger.log(qrcodeLink)
      // Replace the shape with the image
      qrCodeShape.replaceWithImage(qrcodeLink);
    }

    empMainSlide.saveAndClose();

    let pdfFile = DriveApp.getFileById(empSlideId).getAs(MimeType.PDF);

    // Setup the required parameters and send them the email
    let senderName = "Sahajanand Digital";
    let subject = empName + ", Congratualtions!";
    let body = "Please find your certificate attached." + "\n\n Sahajanand Digital team";
    GmailApp.sendEmail(email, subject, body, {
      attachments: [pdfFile],
      name: senderName
    });
    SpreadsheetApp.flush();
  }
}
```