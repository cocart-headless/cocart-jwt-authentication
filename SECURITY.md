# Security Policy

Full details of the CoCart Security Policy can be found on [cocartapi.com/security-policy/](https://cocartapi.com/security-policy/).

## Supported Versions

Generally, only the latest version of CoCart JWT Authentication has continued support. If a critical vulnerability is found in the current version of CoCart JWT Authentication, we may opt to backport any patches to previous versions.

## Reporting a Vulnerability

CoCart JWT Authentication is an open-source plugin for CoCart API.

**For responsible disclosure of security issues, please submit your report based on instructions found on [cocartapi.com/security-policy/](https://cocartapi.com/security-policy/).**

Our most critical targets are:

* CoCart JWT Authentication (this repository)
* cocartapi.com -- the primary marketplace and marketing site.
* cocart.dev -- Developers resources, release updates, guides.

## Guidelines

We're committed to working with security researchers to resolve the vulnerabilities they discover. You can help us by following these guidelines:

*   Pen-testing Production:
    *   Please **setup a local environment** instead whenever possible. Most of our code is open source (see above).
    *   If that's not possible, **limit any data access/modification** to the bare minimum necessary to reproduce a PoC.
    *   **Don't automate form submissions!** That's very annoying for us, because it adds extra work for the volunteers who manage those systems, and reduces the signal/noise ratio in our communication channels.
*   Be Patient - Give us a reasonable time to correct the issue before you disclose the vulnerability.