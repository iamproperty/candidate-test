# Introduction
Welcome to the iamsold Candidate Test.

There are two elements to the candidate test:
- The first is a short coding test to allow us to determine your level of capability and understanding of PHP, as well as your coding style and the level of detail that you work to.
- The second is a scenario, which allows us to determine your understanding of working with requirements that touch multiple applications, and your ability to convert business requirements into code-based requirements.

It is important that both elements are submitted at least 24-hours prior to your second-round interview, to enable our interviewers to review for discussion within the interview. 

## What is required?
Neither of the two elements are exhaustive and should take you less than an hour to do in total, but as this is not a timed test, should you wish to take longer, then you may.

### Coding Test
For the coding test, this repository has two PHP files (named `ICache.php` and `DiskCache.php`, held within the `coding` directory) that we'd like you to review and apply changes to.

The files are generic, and form part of a local-disk caching mechanism that we use within some of our applications.

They have not been reviewed with regard to their performance or adherence to standards for some time.

You are asked to review and to make improvements to the two files, and provide a Pull Request containing the changes and an explanation of why you've made each change.

### Scenario
For the scenario based test, we have received a brief from our Product team, which states:

> Feature: *Bid Registration Modification and Deletion*
> 
> As an **end user**, 
> 
> I want to be able to **modify or delete my Bid Registration myself on the website**
> 
> So that **I can edit or remove my Bid Registration, but only within the bounds set out by iamsold.**

They have also provided the following rules that must be adhered to, as to when a user can edit their registration:

> Editing a Bid Registration should be limited.
> 
> Users must only be able to edit their registration when the property status is not marked as Sold.

In addition, they have provided the following rules that must be adhered to, as to when a user can delete their registration:

> Deleting a Bid Registration should be limited.
> 
> Only those users that have not previously had their registration approved, can delete their registration.

We have had an initial technical review, and have found that this is easily achievable. 

The technical architects have provided the following context and rules to the brief:

- There is currently a WordPress application which hosts our front end website, which uses an API hosted on AWS API Gateway to access our CRM system.
- The endpoints within API Gateway simply act as a forwarder to PHP code held within the CRM system that ultimately carry out the requests.
- Whilst the intention is to move towards use of (serverless) Lambda functions, for now, any changes are to be made in a manner consistent with what is already in place.
- Our database is MySQL and is held on Amazon RDS.
- We have a table containing bid registrations (containing information specifically related to that bid registration).
- This bid registration table is related to both a property record (where things like address, property status and the timestamps for the auction start and end are stored), and a user details record (where the user's unique ID, along with their name and email address are stored), through foreign key columns.
- The GET call will be made to API Gateway upon loading the relevant page within the UI. This will forward the request to the `propertiesBidRegistration` method in the `BidRegistrations.php` file.

We have several code snippets held within the `scenario/crm` directory:

- The `BidRegistrations.php` file contains the code that will be ran from the API call, assume that appropriate and valid parameters have been passed through.
- The `PropertyCard.php` file is used by code in `BidRegistrations.php`.
- The `PropertyBidRegisterManager.php` file is used by code in `BidRegistrations.php`.
- The `Validator.php` file is used by code in `BidRegistrations.php`.

We have several code snippets held within the `scenario/wordpress` directory:

- The `account-bid-registration.twig` file contains the template that is loaded when the user goes to view their bid registrations.
- The `bid-registrations.twig` file contains the template that is loaded from within the `account-bid-registrations.twig` file.
- The `tease-bid-application.twig` file contains the template that is loaded from within the `bid-registrations.twig` file and will ultimately show a property card for each bid registration. This is where the user will decide to edit ("Continue Registration") or delete their registration.

*Not all files and/or methods referenced in the above files are included. In addition, some code has been removed from the methods within the snippets as it is not deemed relevant for this task.*

You are asked to review the code snippets and provide a written solution as to how you would implement the above request. 

You do not need to write any code, it is expected that a word processed document outlining the steps that you would take, to a reasonably technically competent person, will be sufficient, however, should you wish to demonstrate parts of the solution with some code changes, you may form a pull request to do this.

The document/PR should outline:
- What changes, if any, need to be made to the database.
- What changes, if any, need to be made to the code held within the CRM application.
- What changes, if any, need to be made to the code held within the WordPress application.
- What changes, if any, will we see within the API response.
- Any questions that you would need answers to prior to starting this work.

You should be prepared to discuss your proposed solution during the interview. You may receive challenges to parts of your proposal and will be expected to be able to answer any queries.

## Questions
Should you have any questions over the asks in this candidate test, please contact our recruitment team who will direct any queries to the appropriate hiring team.