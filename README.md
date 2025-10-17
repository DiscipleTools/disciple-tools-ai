![Build Status](https://github.com/DiscipleTools/disciple-tools-ai/actions/workflows/ci.yml/badge.svg?branch=master)

![Plugin Banner](https://raw.githubusercontent.com/DiscipleTools/disciple-tools-ai/master/documentation/banner.png)

# Disciple.Tools - AI

This plugin integrates AI capabilities into Disciple.Tools to help disciple makers in their work by providing intelligent search, filtering, summarization, and mapping features.

## Features

- **AI-Powered Search and Filtering**: Use natural language prompts to filter contacts, groups, and other records
- **Magic Link Applications**: Standalone magic link applications with AI-powered search and filtering capabilities
- **Dynamic AI Maps**: AI-generated maps in the metrics section that visualize filtered data geographically
- **Voice Message Transcription**: Record voice messages that are automatically transcribed and used to update contact records

### Future features:

- **Content Summarization**: AI-powered summarization of contact records, activities, and comments
- **PII Protection**: Automatically detects and obfuscates personally identifiable information before sending to AI models

### REST API Integration
- **AI Endpoints**: REST API endpoints for AI summarization and filter creation with secure permission-based access

## Requirements

- Disciple.Tools Theme installed on a WordPress Server
- Compatible LLM API endpoint (OpenAI-compatible)
- Valid API key for your chosen LLM provider

## Configuration

### LLM Settings
After installation, configure your AI provider in the admin settings:

1. **LLM Endpoint**: Your AI provider's API endpoint URL
2. **API Key**: Your authentication key for the AI service
3. **Model**: The specific AI model to use (e.g., `gpt-4`, `claude-3`, etc.)

### Module Settings
The plugin includes several configurable modules:

- **List Search and Filter**: Enable AI search and filter for lists
- **List User App (Magic Link)**: A user app with AI search and filter integrated
- **Metrics Dynamic Maps**: AI-powered maps in the metrics section

## Installing

- Install as a standard Disciple.Tools/WordPress plugin in the system Admin/Plugins area
- Requires the user role of Administrator for initial configuration
- Configure LLM settings in Extensions (D.T) > Disciple Tools AI

## Usage Examples

### Natural Language Filtering
- "Show me all contacts in Kenya who are baptized"
- "Find active groups started this year"
- "List contacts assigned to John with recent activity"

### Geographic Queries
- "Contacts in Nairobi with faith milestone of baptized"
- "Groups meeting in urban areas"

### Status and Timeline Queries
- "New contacts from last month"
- "Inactive groups that need follow-up"
- "Contacts baptized this year"

## Privacy and Security (in progress)

- **Data Protection**: Automatic PII detection, data obfuscation, permission controls, and secure API authentication

## Contribution

Contributions welcome. You can report issues and bugs in the
[Issues](https://github.com/DiscipleTools/disciple-tools-ai/issues) section of the repo. You can present ideas
in the [Discussions](https://github.com/DiscipleTools/disciple-tools-ai/discussions) section of the repo. And
code contributions are welcome using the [Pull Request](https://github.com/DiscipleTools/disciple-tools-ai/pulls)
system for git. For more details on contribution see the
[contribution guidelines](https://github.com/DiscipleTools/disciple-tools-ai/blob/master/CONTRIBUTING.md).
