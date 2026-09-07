# Universal Brothers - Project Reference

**Client:** Universal Brothers (Pvt) Ltd — Hajj/Umrah/Tourism operator
**Framework:** Laravel 12 (PHP) | **Repo:** universal-brothers

Project-specific documentation (architecture, requirements, audits, testing, data verification) lives under [`docs/`](docs/) — see [README.md](README.md) for the full index. Start with `docs/audits/FINAL_AUDIT_REPORT.md` for current status and release-gate decisions.

## Work Logging (MANDATORY — Auto-Active)

The `worklog-tracker` skill MUST run automatically after EVERY task in this project.

- **Skill location:** `.claude/skills/worklog-tracker/SKILL.md`
- **Log storage:** `.project-worklog/daily/{YYYY-MM-DD}.md`
- **Monthly reports:** `.project-worklog/monthly/{YYYY-MM}-report.md`

After completing ANY work (investigation, bug fix, feature, DB query, research, support, deployment, etc.), append an entry to today's daily log file. Never skip. Never wait for the user to ask. This is used for monthly hourly reporting to management.

To generate a monthly report, user will say: "generate monthly report" or "monthly hours" or "create timesheet".
