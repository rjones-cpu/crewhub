# Cursor Ignore Recommendations

Do not overwrite an existing `.cursorignore`. Review and merge appropriate patterns.

Recommended exclusions from AI context and indexing where applicable:

```gitignore
.env
.env.*
!.env.example
storage/logs/**
storage/framework/**
storage/app/private/**
public/storage/**
backups/**
dumps/**
*.sql.gz
*.dump
*.pem
*.key
*.p12
*.pfx
secrets/**
credentials/**
node_modules/**
vendor/**
coverage/**
build/**
dist/**
```

Do not exclude source code, migrations, tests or architecture documents needed for CH-11. Treat production exports, worker evidence and client documents as restricted and avoid including them in AI context unless explicitly approved and sanitized.
