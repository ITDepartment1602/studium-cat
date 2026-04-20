# NGN System — Changes Required (v2.0 Standard)

**Date:** 2026-04-18  
**Reference:** NGN NCSBN Question Bank Development Instructions v2.0 — Section 6 only  
**Scope:** `student/dashboard/ngn/` and all question-type sub-pages  

---

## 1. OVERVIEW

Changes are based strictly on the required fields listed in **Section 6** of the NGN instructions. All 8 question tables (`traditional`, `sata`, `mpr`, `mmr`, `btq`, `highlight`, `dragndrop`, `dropdown`) already have `difficulty_logit`, `furtherinfo`, and `image` columns. The remaining work is: wiring those fields through the frontend (fetch, render, postMessage), saving them to the result tables (`temporary_exam_result` and `exam_results` are missing all 3), and displaying `furtherinfo` + image after the student submits an answer.

---

## 2. FIELD AUDIT PER QUESTION TYPE (Section 6)

### 2.1 Multiple Choice (MC)
| Field | Status |
|---|---|
| concept | ✅ Present |
| topic | ✅ Present |
| question | ✅ Present |
| choices | ✅ Present |
| correct | ✅ Present |
| rationale | ✅ Present |
| system | ✅ Present |
| cnc | ✅ Present |
| narcan | ✅ Present |
| dlevel | ✅ Present |
| difficulty_logit | ✅ Present |
| furtherinfo | ✅ Present |
| image | ✅ Present |

### 2.2 SATA (Select All That Apply)
| Field | Status |
|---|---|
| concept | ✅ Present |
| topic | ✅ Present |
| question | ✅ Present |
| items | ✅ Present |
| correct | ✅ Present |
| rationale | ✅ Present |
| system | ✅ Present |
| cnc | ✅ Present |
| narcan | ✅ Present |
| dlevel | ✅ Present |
| difficulty_logit | ✅ Present |
| furtherinfo | ✅ Present |
| image | ✅ Present |

### 2.3 MPR (Multiple Response)
| Field | Status |
|---|---|
| concept | ✅ Present |
| case_title | ✅ Present |
| topic | ✅ Present |
| question | ✅ Present |
| items | ✅ Present |
| correct | ✅ Present |
| rationale | ✅ Present |
| system | ✅ Present |
| cnc | ✅ Present |
| narcan | ✅ Present |
| dlevel | ✅ Present |
| difficulty_logit | ✅ Present |
| furtherinfo | ✅ Present |
| image | ✅ Present |

### 2.4 Dropdown (Cloze)
| Field | Status |
|---|---|
| concept | ✅ Present |
| topic | ✅ Present |
| question | ✅ Present |
| passage | ✅ Present |
| options | ✅ Present |
| highlightable | ✅ Present |
| correct_words | ✅ Present |
| rationale | ✅ Present |
| system | ✅ Present |
| cnc | ✅ Present |
| narcan | ✅ Present |
| dlevel | ✅ Present |
| difficulty_logit | ✅ Present |
| furtherinfo | ✅ Present |
| image | ✅ Present |

### 2.5 MMR (Matrix Multiple Response)
| Field | Status |
|---|---|
| concept | ✅ Present |
| case_title | ✅ Present |
| topic | ✅ Present |
| question | ✅ Present |
| columns | ✅ Present |
| rows | ✅ Present |
| correct | ✅ Present |
| rationale | ✅ Present |
| nurses_notes | ✅ Present |
| vital_signs | ✅ Present |
| diagnostics | ✅ Present |
| system | ✅ Present |
| cnc | ✅ Present |
| narcan | ✅ Present |
| dlevel | ✅ Present |
| screen_no | ✅ Present |
| total_screens | ✅ Present |
| difficulty_logit | ✅ Present |
| furtherinfo | ✅ Present |
| image | ✅ Present |

### 2.6 Drag and Drop
| Field | Status |
|---|---|
| concept | ✅ Present |
| topic | ✅ Present |
| question | ✅ Present |
| items | ✅ Present |
| correct | ✅ Present |
| rationale | ✅ Present |
| system | ✅ Present |
| cnc | ✅ Present |
| narcan | ✅ Present |
| dlevel | ✅ Present |
| difficulty_logit | ✅ Present |
| furtherinfo | ✅ Present |
| image | ✅ Present |

### 2.7 Highlight
| Field | Status |
|---|---|
| concept | ✅ Present |
| topic | ✅ Present |
| question | ✅ Present |
| passage | ✅ Present |
| options | ✅ Present |
| highlightable | ✅ Present |
| correct_words | ✅ Present |
| rationale | ✅ Present |
| system | ✅ Present |
| cnc | ✅ Present |
| narcan | ✅ Present |
| dlevel | ✅ Present |
| maxHighlights | ✅ Present |
| difficulty_logit | ✅ Present |
| furtherinfo | ✅ Present |
| image | ✅ Present |

### 2.8 Bowtie
| Field | Status |
|---|---|
| concept | ✅ Present |
| topic | ✅ Present |
| type | ✅ Present |
| question | ✅ Present |
| item | ✅ Present |
| targets | ✅ Present |
| actionToTake | ✅ Present |
| potentialConditions | ✅ Present |
| parametersToMonitor | ✅ Present |
| nursesNotes | ✅ Present |
| vitalSigns | ✅ Present |
| diagnostics | ✅ Present |
| correct | ✅ Present |
| rationale | ✅ Present |
| system | ✅ Present |
| cnc | ✅ Present |
| narcan | ✅ Present |
| dlevel | ✅ Present |
| difficulty_logit | ✅ Present |
| furtherinfo | ✅ Present |
| image | ✅ Present |

---

## 3. SUMMARY — WHAT NEEDS TO BE ADDED

All 8 question tables already have `difficulty_logit`, `furtherinfo`, and `image`. **No question table ALTERs are needed.**

The only database changes required are on the two result tables, which are missing these fields:

| Field | Type | Description |
|---|---|---|
| `difficulty_logit` | DECIMAL(4,2) | IRT difficulty value from −3.0 to +3.0. Saved with each answer for CAT and review. |
| `furtherinfo` | TEXT | Teaching notes shown after the student submits, below the rationale. Saved so review page can display it. |
| `image` | VARCHAR(255) | Question image filename. Saved with the answer record for reference. |

---

## 4. DATABASE CHANGES

### 4.1 ALTER result tables only

Add the 3 fields to `temporary_exam_result` and `exam_results` so answers carry this data through the full flow:

```sql
ALTER TABLE `temporary_exam_result`
    ADD COLUMN `difficulty_logit` DECIMAL(4,2) NULL AFTER `dlevel`,
    ADD COLUMN `furtherinfo`      TEXT         NULL AFTER `difficulty_logit`,
    ADD COLUMN `image`            VARCHAR(255) NULL AFTER `furtherinfo`;

ALTER TABLE `exam_results`
    ADD COLUMN `difficulty_logit` DECIMAL(4,2) NULL AFTER `dlevel`,
    ADD COLUMN `furtherinfo`      TEXT         NULL AFTER `difficulty_logit`,
    ADD COLUMN `image`            VARCHAR(255) NULL AFTER `furtherinfo`;
```

---

## 5. CHANGES PER QUESTION TYPE FILE

Every `{type}/index.php` needs these 3 updates:

### 5.1 DB SELECT — add new fields
```php
// Add to existing SELECT:
SELECT ..., difficulty_logit, furtherinfo, image
FROM {table} WHERE id = ?
```

### 5.2 PHP → JS variable injection — add new vars
```php
var difficulty_logit = <?= json_encode((float)($row['difficulty_logit'] ?? 0)) ?>;
var furtherinfo      = <?= json_encode($row['furtherinfo'] ?? '') ?>;
var image            = <?= json_encode($row['image'] ?? '') ?>;
```

### 5.3 Image rendering — show above the question if set
```html
<!-- Add above the question text block -->
<div id="questionImageWrap" style="<?= empty($row['image']) ? 'display:none;' : '' ?>">
    <img src="/studium-cat/student/dashboard/ngn/images/<?= htmlspecialchars($row['image'] ?? '') ?>"
         alt="Question Image" style="max-width:100%; border-radius:6px; margin-bottom:12px;" />
</div>
```

### 5.4 Further Info block — show after rationale on submit
Add this HTML below the existing rationale block (hidden by default):
```html
<div id="furtherInfoBlock" style="display:none; margin-top:12px;
     background:#fffbeb; border-left:4px solid #f59e0b;
     padding:10px 14px; border-radius:0 6px 6px 0;">
    <div style="font-weight:700; color:#92400e; font-size:0.82rem; margin-bottom:6px;">
        📝 Further Information
    </div>
    <div id="furtherInfoText" style="font-size:0.88rem; color:#374151; line-height:1.5;"></div>
</div>
```

In the JS answer-reveal function (wherever rationale is shown), add:
```javascript
if (furtherinfo) {
    document.getElementById('furtherInfoText').textContent = furtherinfo;
    document.getElementById('furtherInfoBlock').style.display = 'block';
}
```

### 5.5 `postMessage` payload — add new fields
```javascript
window.parent.postMessage({
    type: 'answered',
    // ... all existing fields ...
    difficulty_logit: difficulty_logit,  // NEW
    furtherinfo: furtherinfo,            // NEW
    image: image                         // NEW
}, '*');
```

---

## 6. CHANGES TO `save_history.php`

Add the 3 new fields to the INSERT:

```php
// In the column list:
difficulty_logit, furtherinfo, image

// In the VALUES (read from POST):
$_POST['difficulty_logit'] ?? 0,
$_POST['furtherinfo'] ?? '',
$_POST['image'] ?? ''
```

---

## 7. CHANGES TO `index.php` (Main Exam Container)

### 7.1 `postMessage` receiver — extract new fields
```javascript
// In the message event listener, add:
ans.difficulty_logit = data.difficulty_logit || 0;
ans.furtherinfo      = data.furtherinfo || '';
ans.image            = data.image || '';
```

### 7.2 `prefill` postMessage — pass new fields during review
```javascript
iframe.contentWindow.postMessage({
    type: 'prefill',
    // ... existing fields ...
    difficulty_logit: ans.difficulty_logit,
    furtherinfo: ans.furtherinfo,
    image: ans.image,
    showRationale: true,
    isReview: true
}, '*');
```

### 7.3 `save_history.php` fetch call — include new fields in POST body
```javascript
difficulty_logit: ans.difficulty_logit,
furtherinfo:      ans.furtherinfo,
image:            ans.image
```

---

## 8. CHANGES TO QUESTION IFRAMES — `prefill` handler

Each `{type}/index.php` already handles a `prefill` message to restore a previously answered question. The handler needs to also restore the Further Info and image when reviewing:

```javascript
window.addEventListener('message', function(e) {
    if (e.data.type === 'prefill') {
        // ... existing restore logic ...

        // NEW: restore furtherinfo
        if (e.data.furtherinfo) {
            document.getElementById('furtherInfoText').textContent = e.data.furtherinfo;
            document.getElementById('furtherInfoBlock').style.display = 'block';
        }

        // NEW: restore image (already rendered server-side, but update src if needed)
        if (e.data.image) {
            var wrap = document.getElementById('questionImageWrap');
            if (wrap) wrap.style.display = 'block';
        }
    }
});
```

---

## 9. CHANGES TO `history_details.php` (Review Page)

Add `difficulty_logit` and `furtherinfo` to the per-question review display.

- **`difficulty_logit`**: Show numeric logit value next to the existing `dlevel` badge in the NGN Metrics column (e.g., `Hard (1.8)`).
- **`furtherinfo`**: Add a collapsible row under the Rationale column. Show a "Further Info ▾" toggle that expands to show the text.

---

## 10. IMAGES FOLDER

Create the directory:
```
student/dashboard/ngn/images/
```

All `image` filenames from question records resolve to this path. Empty or NULL `image` field = no image rendered.

---

## 11. IMPLEMENTATION ORDER

1. **DB migrations** — run ALTERs on `temporary_exam_result` and `exam_results` only (Section 4) — question tables already done
2. **`save_history.php`** — add 3 new fields to INSERT (Section 6)
3. **`index.php`** — update message receiver, prefill sender, save fetch body (Section 7)
4. **Each question iframe** — add SELECT fields, JS vars, image block, Further Info block, postMessage fields, prefill handler (Section 5 + Section 8) — one type at a time
5. **`history_details.php`** — add logit display + furtherinfo toggle (Section 9)
6. **`images/` folder** — create directory (Section 10)

---

## 12. CHANGE SUMMARY TABLE

| File | What Changes |
|---|---|
| All 8 question DB tables | ✅ Already have all 3 fields — no change needed |
| `temporary_exam_result` | ADD `difficulty_logit`, `furtherinfo`, `image` |
| `exam_results` | ADD `difficulty_logit`, `furtherinfo`, `image` |
| `save_history.php` | Add 3 fields to INSERT |
| `index.php` | Extract + forward 3 new fields in message flow |
| `mc/index.php` | Fetch fields, show image, show furtherinfo after submit |
| `sata/index.php` | Fetch fields, show image, show furtherinfo after submit |
| `mpr/index.php` | Fetch fields, show image, show furtherinfo after submit |
| `mmr/index.php` | Fetch fields, show image, show furtherinfo after submit |
| `bowtie/index.php` | Fetch fields, show image, show furtherinfo after submit |
| `highlight/index.php` | Fetch fields, show image, show furtherinfo after submit |
| `dragndrop/index.php` | Fetch fields, show image, show furtherinfo after submit |
| `dropdown/index.php` | Fetch fields, show image, show furtherinfo after submit |
| `history_details.php` | Show logit value + furtherinfo toggle in review |
| `ngn/images/` | Create new folder |

---

*End of NGN Changes Documentation v2.0*
