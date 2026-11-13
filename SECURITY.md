# Security

## Reporting a problem

Please do not open a public issue for a security problem.

Report it privately instead. On GitHub, open the **Security** tab of this repository and choose **Report a vulnerability**. If that does not work for you, write to **hello@albertoarena.it**.

Say what you found, how to reproduce it, and which version of Sudo Hire you ran. You will get a reply within a week. Once a fix is released, you are credited in the release notes unless you would rather not be.

## Supported versions

Only the latest release gets security fixes. To get a fix, update.

## What counts

Sudo Hire reads job posts from public job boards and company career pages. Anyone can write a job post, so the app treats all job text as untrusted. Things worth reporting include:

- A job post that makes the app open anything other than an `http` or `https` link
- Job text that runs a script in the app window, or that changes the app outside of the job list
- The app running a command or AppleScript built from job text, for example through the notification
- A source response that crashes the app or hangs it
- A company entry that makes the app send a request to a host other than its ATS provider
- The app sending anything over the network other than the requests described in [PRIVACY.md](PRIVACY.md)
- The app reading a source without a certificate check
- An official release that is not signed and notarised, or that Gatekeeper warns about

A job that is fake, wrong or offensive is not a security problem in the app. Report it to the job board that lists it. A job with a wrong country, salary or seniority is a parser bug: open an issue, see [CONTRIBUTING.md](CONTRIBUTING.md).
