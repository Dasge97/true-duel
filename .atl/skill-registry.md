# Skill Registry

**Delegator use only.** Any agent that launches sub-agents reads this registry to resolve compact rules, then injects them directly into sub-agent prompts. Sub-agents do NOT read this registry or individual SKILL.md files.

See `_shared/skill-resolver.md` for the full resolution protocol.

## User Skills

| Trigger | Skill | Path |
|---------|-------|------|
| When creating a GitHub issue, reporting a bug, or requesting a feature. | issue-creation | /home/codehive/.config/opencode/skills/issue-creation/SKILL.md |
| When creating a pull request, opening a PR, or preparing changes for review. | branch-pr | /home/codehive/.config/opencode/skills/branch-pr/SKILL.md |
| When writing Go tests, using teatest, or adding test coverage. | go-testing | /home/codehive/.config/opencode/skills/go-testing/SKILL.md |
| When user asks to create a new skill, add agent instructions, or document patterns for AI. | skill-creator | /home/codehive/.config/opencode/skills/skill-creator/SKILL.md |
| When creating a basic WordPress store, setting up WooCommerce catalog pages/products/categories, or preparing WP Sandbox as an ecommerce demo. | wp-sandbox-store-bootstrap | /home/codehive/.config/opencode/skills/wp-sandbox-store-bootstrap/SKILL.md |
| When user says "judgment day", "judgment-day", "review adversarial", "dual review", "doble review", "juzgar", "que lo juzguen". | judgment-day | /home/codehive/.config/opencode/skills/judgment-day/SKILL.md |

## Compact Rules

Pre-digested rules per skill. Delegators copy matching blocks into sub-agent prompts as `## Project Standards (auto-resolved)`.

### issue-creation
- Always create issues from templates; blank issues are disallowed.
- Search duplicates before creating (`gh issue list --search`).
- Every new issue starts with `status:needs-review`.
- PRs are blocked until maintainers add `status:approved`.
- Use Discussions for questions; issues are for bugs/features only.

### branch-pr
- Every PR must link one approved issue (`Closes/Fixes/Resolves #N`).
- Branch names must match `type/description` with allowed types and lowercase slug.
- Add exactly one `type:*` label matching PR intent.
- Use conventional commits; never include Co-Authored-By trailers.
- Ensure required checks pass before merge (issue link, approved label, PR type, shellcheck).

### go-testing
- Prefer table-driven tests for pure logic and error path coverage.
- Test Bubbletea state transitions by calling `Model.Update()` directly.
- Use `teatest.NewTestModel()` for interactive TUI flow tests.
- Use golden files for stable view output snapshots.
- Use `t.TempDir()` and dependency seams for side effects/system calls.

### skill-creator
- Create skills only for reusable, non-trivial recurring patterns.
- Use `skills/{skill-name}/SKILL.md` with complete frontmatter and Trigger text.
- Keep guidance concise: critical patterns, minimal examples, commands.
- Use local references only; avoid web URLs in `references/`.
- Register new skills in project `AGENTS.md` when applicable.

### wp-sandbox-store-bootstrap
- Treat work as content/config workflow; do not edit theme code by default.
- Audit first: plugins, theme, pages, product counts, taxonomies, categories.
- Reuse existing WooCommerce core pages; avoid recreating catalog basics.
- Create products as `post_type: product` with required WooCommerce meta and category.
- Do not delete existing products/pages/terms unless explicitly requested.

### judgment-day
- Resolve skill rules from registry before launching judges.
- Run two blind judges in parallel; never sequential and never self-review.
- Synthesize findings into confirmed/suspect/contradiction and classify warning realism.
- Ask user confirmation before first fix round on confirmed issues.
- Re-judge only for confirmed critical issues after round one; avoid endless loops.

## Project Conventions

| File | Path | Notes |
|------|------|-------|
| — | — | No project convention files detected (`agents.md`, `AGENTS.md`, `CLAUDE.md`, `.cursorrules`, `GEMINI.md`, `copilot-instructions.md`). |

Read the convention files listed above for project-specific patterns and rules. All referenced paths have been extracted — no need to read index files to discover more.
