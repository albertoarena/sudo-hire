# Contributing

Thank you for helping. The easiest way to help needs no code: send a job post that the app reads wrong.

## Reporting a wrong job

Sudo Hire reads free text from job posts: where you can work from, the salary, the contract type and the seniority. Real job text is messy, and each new example makes the parsers better.

Open an issue with:

- The source, for example Greenhouse, Remotive or We Work Remotely, and the link to the job
- The exact text that the app read, for example `Remote, EMEA (excluding UK)` or `€60k - €75k + equity`
- What the app showed, and what you expected

Copy the text exactly as the job post gives it. The tests use real strings, so a real string is more useful than a description of it. Do not include names, emails or phone numbers from the post.

## Suggesting a source

A new job board or ATS provider can become a source when:

- It has a public API or feed, with no key and no login
- Its terms allow an app to read it, and to show its jobs with a link back
- It lists developer jobs, preferably PHP or Laravel jobs, with a location

Open an issue first, with a link to the API and to the terms. Sources that need an API key are planned for later, with your own key. Sites with no public API, or with terms that forbid it, cannot become a source.

## Changing the code

Read [AGENTS.md](AGENTS.md) first. It has the stack, the architecture and the rules, and it applies to people as well as tools. In short:

- **Tests first.** Write a failing test, make it pass, then clean up. Pest for PHP, Vitest for Vue
- **Fixtures are real responses**, trimmed, in `tests/Fixtures/<source>/`. Remove any personal data
- **No live network in tests.** CI runs offline
- **Follow the source terms.** Every job keeps its source name, its original link and its original apply link. Never replace the apply link
- **The job schema and the `JobSource` interface are frozen.** Open an issue before you change them
- Commit subjects are short and in the imperative, like `Add the Personio reader`
- **No AI attribution:** no `Co-Authored-By` trailers for AI tools in commits, and no "Generated with" footers in pull requests, issues or comments. Using a tool is fine; you are the author of what you submit
- Every change goes through a pull request

Run all the checks before you open a pull request. CI runs the same ones:

```bash
npm run build
composer test
composer analyse
vendor/bin/pint --test
npm test
npm run typecheck
```

The app itself runs only on macOS. To set it up, run `composer setup`. To start it, run `composer native:dev`.

## License of contributions

Sudo Hire is released under the [MIT license](LICENSE). By opening a pull request, you agree that your contribution is licensed under the MIT license too.

Future versions of Sudo Hire may be released under other terms. The MIT license allows this, and contributions already released under MIT stay available under MIT.

## Not affiliated with any job board

Sudo Hire is not affiliated with any job board or ATS provider. Please do not add their logos anywhere. Use their names as text, to say where a job comes from.
