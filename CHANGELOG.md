# Changelog

# 1.1.1 (2025-05-05)
- Fixed a bug with accidentally included code in `ParallelAutoMergeStrategy`

# 1.1.0 (2025-05-04)

- Updated `open-ai/client` to v0.12.0 for Gemini support
- Fixed a few things for more reliable Gemini support
    - Note: Gemini does not support return types as array (e.g. `"type": ["array", "null"]`) in the JSON schema, and OpenAI client _still_ does not catch that error properly, leading to an error related to `choices` not being found.
- Added native support for the following newer models:
  - `google/gemini-2.0-flash-lite` no longer used the `preview` tag
  - `google/gemini-2.5-pro-preview-03-25` added as experimental
  - `google/gemini-2.5-pro-exp-03-25` added as experimental
- Added beta support for Eloquent and Filament tools