# Example Project Memory Template

This is a template for creating well-structured project memory files.

## PROJECT_MEMORY.md Template

```markdown
# PROJECT: [Project Name]

**Created**: [Date]  
**Status**: [Active/Planning/On Hold/Complete]  
**Priority**: [High/Medium/Low]

## Overview

Brief description of what this project is about.

## Goals

- Primary goal
- Secondary goals
- Success criteria

## Current State

### What Works
- Feature A implemented
- Feature B tested
- Deployment configured

### In Progress
- Working on Feature C
- Debugging Issue X

### Blocked
- Waiting for API access
- Need design approval

## Technical Details

### Stack
- Language: [e.g., Python 3.11]
- Framework: [e.g., FastAPI]
- Database: [e.g., PostgreSQL]
- Hosting: [e.g., AWS EC2]

### Architecture
Brief description of system architecture.

### Key Files
- `main.py` - Entry point
- `config.py` - Configuration
- `models.py` - Data models

### Dependencies
- requests==2.28.0
- pandas==1.5.0

## Context for AI

Important background information that helps AI understand:
- Business logic
- Design decisions
- Known limitations
- API specifications

## Links & Resources

- GitHub: [URL]
- Documentation: [URL]
- Production: [URL]
- Staging: [URL]
```

## INSIGHTS_LOG.md Template

```markdown
# INSIGHTS LOG: [Project Name]

Log of important learnings, decisions, and discoveries.

---

## [Date] - [Title]

**Context**: What prompted this insight

**Discovery**: What was learned

**Impact**: How this affects the project

**Action**: What changed as a result

---

## [Date] - Performance Optimization

**Context**: App was slow on large datasets

**Discovery**: N+1 query problem in user fetching

**Impact**: 300ms -> 50ms response time

**Action**: Implemented eager loading

---

## [Date] - Design Decision

**Context**: Choosing between REST and GraphQL

**Discovery**: Our use case is simple CRUD

**Impact**: Reduced complexity

**Action**: Went with REST API

---
```

## NEXT_ACTIONS.md Template

```markdown
# NEXT ACTIONS: [Project Name]

Prioritized list of tasks and actions.

## Immediate (This Week)

- [ ] Fix bug in authentication
- [ ] Add error handling to API
- [ ] Write tests for user module

## Short Term (This Month)

- [ ] Implement caching layer
- [ ] Add monitoring dashboard
- [ ] Optimize database queries

## Long Term (This Quarter)

- [ ] Refactor legacy code
- [ ] Add internationalization
- [ ] Scale to multi-region

## Ideas / Backlog

- Real-time notifications
- Mobile app version
- AI-powered recommendations

## Blocked / Waiting

- API key from vendor (requested 2023-11-01)
- Design mockups from team
- Legal approval for feature X

---

**Last Updated**: [Date]  
**Next Review**: [Date]
```

## Usage

1. Copy templates to your project directory
2. Fill in project-specific information
3. Update regularly as project progresses
4. Use as foundation for AI context loading

## Tips

### For PROJECT_MEMORY.md
- Keep overview concise
- Update technical details as stack changes
- Include links to external resources
- Note important design decisions

### For INSIGHTS_LOG.md
- Log learnings immediately
- Include date for timeline
- Note both successes and failures
- Link to related issues/PRs

### For NEXT_ACTIONS.md
- Review weekly
- Prioritize ruthlessly
- Move completed items to insights
- Keep blocked items visible

## See Also

- [Main Documentation](../docs/)
- [API Reference](../docs/API.md)
- [Contributing Guide](../CONTRIBUTING.md)
