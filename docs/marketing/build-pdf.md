# How to Build `features-brochure.pdf` on Windows 11

You have a polished markdown brochure (`features-brochure.md`) and a matching self-contained HTML file (`features-brochure.html`). Here are the three easiest ways to turn them into a PDF on your Windows 11 machine, ordered from fastest to most involved.

---

## Option 1 (Fastest, Zero Install): Open the HTML in a Browser and Print to PDF

This is the recommended path. The `features-brochure.html` file is already **print-ready** — it has embedded CSS, A4 margins, page-break rules, Google-Fonts typography (Cairo for Arabic, Inter for English), and an accent color.

1. Open File Explorer and navigate to `docs/marketing/`.
2. Double-click `features-brochure.html`. It opens in your default browser (Edge or Chrome both work).
3. Press **Ctrl + P** to open the print dialog.
4. Set **Destination** to `Save as PDF`.
5. Set **Layout** to `Portrait` and **Paper size** to `A4`.
6. Under **More settings**, make sure:
   - **Margins:** Default
   - **Scale:** Default (100%)
   - **Background graphics:** **ON** (this is important — it keeps the teal accent bars and cover-page styling).
7. Click **Save**. Name it `features-brochure.pdf`.

Done. No installs, no command line.

---

## Option 2: VS Code + "Markdown PDF" Extension

If you prefer working from the markdown source:

1. Open VS Code.
2. Go to **Extensions** (Ctrl + Shift + X).
3. Search for `yzane.markdown-pdf` or paste this ID directly: `yzane.markdown-pdf`
4. Click **Install**.
5. Open `docs/marketing/features-brochure.md` in VS Code.
6. Right-click anywhere in the editor and choose **Markdown PDF: Export (pdf)**.
7. The PDF appears next to the markdown file after about 10–20 seconds.

The extension ships with a bundled headless Chromium, so you don't need Chrome in your PATH.

---

## Option 3: Typora (Nice Markdown Editor)

1. Download Typora from https://typora.io (paid, but has a trial).
2. Open `features-brochure.md` in Typora.
3. **File → Export → PDF**.
4. Save.

Typora produces slightly prettier typography out of the box than VS Code's markdown-pdf extension.

---

## Option 4 (Advanced): Pandoc + wkhtmltopdf

If you want to scriptify PDF generation:

```powershell
winget install JohnMacFarlane.Pandoc
winget install wkhtmltopdf.wkhtmltopdf
```

Then from the `docs/marketing/` directory:

```powershell
pandoc features-brochure.md -o features-brochure.pdf --pdf-engine=wkhtmltopdf
```

**Caveats:** Pandoc's default HTML → PDF output is less pretty than our hand-styled HTML. For the best-looking result, use Option 1.

---

## Troubleshooting

- **Arabic text displays as boxes or missing glyphs** — your browser didn't load the Google Font. Check that you have internet access when opening the HTML. Or swap the `<link>` for an offline Cairo font file.
- **Page breaks in wrong places** — some browsers honor `page-break-before` better than others. Edge and Chrome both work well; Firefox can be inconsistent.
- **Missing teal accent color in the PDF** — you forgot to enable **Background graphics** in the print dialog. Go back and toggle it on.
- **Cover page runs into the next section** — this shouldn't happen, but if it does, open the HTML and confirm the `<h2>` elements are triggering `page-break-before: always`.

---

## Which file do I edit?

- **Edit `features-brochure.md`** if you want to change the wording. It's the source of truth.
- **Edit `features-brochure.html`** if you want to change the styling (colors, fonts, spacing).
- The HTML currently mirrors the markdown content. If you change the markdown substantially, update the HTML to match — or regenerate it.
