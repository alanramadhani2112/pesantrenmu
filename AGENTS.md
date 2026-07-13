# Agent Instructions

## Tool Usage

- `apply_patch` is a FREEFORM tool.
- Never call `apply_patch` with JSON or a `cmd` key.
- Correct format:

```diff
*** Begin Patch
*** Update File: path/to/file
@@
-old
+new
*** End Patch
```

- Wrong format: `{"cmd":"*** Begin Patch\n..."}`

## Project Defaults

- Keep changes minimal and focused.
- Match existing Laravel Blade and Metronic style.
- Run the smallest relevant check after non-trivial edits.
- UI changes must preserve Metronic components and existing design tokens.
