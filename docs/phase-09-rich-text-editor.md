# PHASE 9 — RICH TEXT EDITOR & CONTENT AUTHORING SYSTEM

## 1. Editor System Architecture

### Core Components
```
┌──────────────────────────────────────────┐
│              Editor UI Layer              │
│  TipTap Editor / Toolbar / Bubble Menu   │
├──────────────────────────────────────────┤
│           Content State Manager          │
│        ProseMirror document model        │
├──────────────────────────────────────────┤
│            Autosave Engine               │
│      Debounce → API → Redis → DB        │
├──────────────────────────────────────────┤
│             Media Manager                │
│     Upload / Embed / Gallery / Drag      │
├──────────────────────────────────────────┤
│          AI Assistant Layer              │
│   Generate / Rewrite / Improve / Expand  │
├──────────────────────────────────────────┤
│          SEO Analyzer Layer              │
│    Real-time score / Suggestions / Meta  │
├──────────────────────────────────────────┤
│           Revision Tracker               │
│    Snapshot / Diff / Restore / Compare   │
└──────────────────────────────────────────┘
```

---

## 2. Editor Technology Stack

| Component | Technology | License |
|-----------|-----------|---------|
| Editor Core | TipTap (ProseMirror) | MIT |
| Frontend | Blade + Alpine.js | MIT |
| Build | Vite | MIT |
| Backend | Laravel 12 | MIT |
| AI | NVIDIA API | Free tier |
| Cache | Redis | BSD |

**Why TipTap:** Extensible, modern, free core, great Alpine.js compatibility, block-based architecture.

---

## 3. Editor Features

### Core Formatting
- Bold, italic, underline, strikethrough
- Headings H2-H6 (H1 reserved for title)
- Ordered/unordered lists
- Blockquotes with citation
- Code blocks with language selection
- Inline code
- Horizontal rules
- Tables with add/delete rows/columns
- Links (internal + external)
- Images (inline, aligned left/center/right)
- Video embeds (YouTube, Vimeo)
- Custom HTML blocks (sandboxed)

### Advanced
- Block drag-and-drop reordering
- Markdown toggle
- Focus mode (hide toolbar except on selection)
- Character/word count

---

## 4. Autosave System

### Behavior
| Trigger | Action | Debounce |
|---------|--------|----------|
| Key input | Schedule save | 5 seconds |
| Content paste | Immediate save | — |
| Image upload | Wait for upload | — |
| Tab switch/blur | Immediate save | — |

### Flow
```
Editor input → 5s debounce → PATCH /api/posts/{id}/autosave →
Store in Redis temp → Persist to DB → Create revision
```

- Redis key: `autosave:{post_id}:{user_id}`
- TTL: 86400s (1 day)
- Conflict: Last-write-wins with timestamp check

---

## 5. Content Block System

| Block Type | Content | Editor Component |
|-----------|---------|-----------------|
| Text | Rich text | TipTap Paragraph |
| Heading | H2-H6 | TipTap Heading |
| Image | Image URL + alt | Custom node |
| Gallery | Multiple images | Custom node |
| Video | Embed URL | Custom node |
| Code | Preformatted | TipTap CodeBlock |
| Quote | Quote + citation | TipTap Blockquote |
| Divider | HR | TipTap HorizontalRule |
| AI Generated | Rich text | Inserted on generation |

Blocks are drag-reorderable within the editor.

---

## 6. Media Embedding System

- **Upload:** Drag-drop or file picker → AJAX upload via Media Library
- **Format:** Auto-convert to WebP, generate responsive srcset
- **Embed:** Paste YouTube/Vimeo URL → auto-convert to embed
- **Gallery:** Multi-image block with lightbox

---

## 7. AI Writing Assistant (NVIDIA API)

### Features
| Action | Input | Output |
|--------|-------|--------|
| Generate section | Topic sentence | 2-3 paragraph expansion |
| Rewrite | Selected text | Improved version |
| Improve readability | Selected text | Simpler, clearer version |
| Expand | Selected text | Detailed version |
| Summarize | Selected text | 2-3 sentence summary |
| Generate headings | Content | H2/H3 suggestions |
| Fix grammar | Selected text | Corrected version |
| Insert keywords | Content | SEO keyword suggestions |

### Workflow
1. User selects text (or places cursor)
2. Clicks AI wand icon in toolbar
3. Choose action from dropdown
4. NVIDIA API called with context
5. Result previewed in modal
6. User accepts/edits/discards
7. On accept, content inserted/replaces selection

---

## 8. SEO Integration Layer

### Real-time SEO Panel (sidebar)
| Metric | Display | Target |
|--------|---------|--------|
| SEO Score | 0-100 gauge | >70 |
| Readability Score | 0-100 gauge | >60 |
| Keyword Density | Percentage | 1-3% |
| Title Length | Character count | 50-60 |
| Description Length | Character count | 150-160 |
| Heading Count | Numbers | At least 1 H2 per 300 words |
| Image Alt Text | Missing count | 0 missing |
| Internal Links | Count | At least 2 |
| Word Count | Number | >800 |

---

## 9. Revision Tracking System

- Auto-snapshot on every status change and significant edit
- Stored in `post_revisions` table
- Compare: side-by-side diff view
- Restore: copy revision content to post (creates new revision)
- AI: "What changed in this revision?" summary

---

## 10. Content Validation Engine

### Pre-Publish Checklist
- [ ] Title between 30-255 chars
- [ ] SEO meta title between 30-60 chars
- [ ] SEO meta description between 100-160 chars
- [ ] At least one H2 heading
- [ ] No empty paragraphs
- [ ] All images have alt text
- [ ] Featured image set
- [ ] Word count > 300
- [ ] Readability score > 50

---

## 11. Collaboration (Future-Ready)

Architecture prepared for:
- Real-time collaborative editing (WebSockets)
- Inline comments and suggestions
- Change tracking with accept/reject
- Approval workflows within editor

---

## 12. Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| Ctrl+B | Bold |
| Ctrl+I | Italic |
| Ctrl+U | Underline |
| Ctrl+K | Insert link |
| Ctrl+Shift+K | Remove link |
| Ctrl+S | Save draft |
| Ctrl+Shift+E | AI expand |
| Ctrl+Shift+R | AI rewrite |
| Ctrl+Shift+G | AI grammar fix |
| Tab | Indent list |
| Shift+Tab | Outdent list |

---

## 13. Performance Optimization

- Lazy rendering: Only render visible blocks
- Debounced input: 150ms before processing
- Debounced autosave: 5s after last change
- Redis: Draft content cached, synced to DB periodically
- Virtual scrolling for long documents

---

## 14. Draft Storage System

| Storage Type | When Used | TTL |
|-------------|-----------|-----|
| Redis | Active editing session | 24h |
| MySQL | Post record (status = draft) | Permanent |
| Session | Unsaved new post | Session end |

---

## 15. Error Handling System

- **Network failure:** Queue failed saves, retry on reconnect
- **AI failure:** Show error toast, allow manual retry
- **Autosave conflict:** Show warning if newer server version exists
- **Upload failure:** Show error per file, continue with successful ones

---

## 16. Security System

- XSS: TipTap's built-in HTML sanitization + server-side HTML Purifier
- HTML injection: Allowlist of permitted tags/attributes
- Upload validation: MIME type, size, malware scan (ClamAV optional)
- Autosave rate limiting: 1 request per 3 seconds per user

---

## 17. User Experience Design

| Mode | Description |
|------|-------------|
| Normal | Toolbar + sidebar + footer |
| Distraction-free | Hidden toolbar, minimal UI |
| Fullscreen | Editor fills entire viewport |
| Dark mode | Dark theme for editor |

---

## 18. Content Export System

| Format | Use Case |
|--------|----------|
| HTML | Copy-paste to other platforms |
| Markdown | Developers, migration |
| JSON | API, backup, migration |
| Plain text | Word count, analysis |

---

## 19. Analytics Integration

- Writing time tracked per session
- AI usage per user (tokens consumed)
- Edit frequency: edits per hour
- SEO score improvement over revisions

---

## 20. Final Output

**Phase 9 complete.** Rich Text Editor & Content Authoring System:
- TipTap-based editor with full formatting
- Block-based content system (9 block types)
- AI writing assistant (8 actions via NVIDIA)
- Real-time SEO scoring panel
- Autosave with Redis + DB persistence
- Revision tracking with compare/restore
- Pre-publish validation checklist
- Media embedding with WebP optimization
- Performance optimizations (lazy, debounce, cache)
- Keyboard shortcuts (15+)
- Dark mode and distraction-free writing
- Multi-format export (HTML, Markdown, JSON)

Ready to proceed to **Phase 10**.
