import fs from 'fs';
import path from 'path';

function scanButtonsInDir(dir) {
  const issues = [];
  let totalButtons = 0;

  function walk(current) {
    if (!fs.existsSync(current)) return;
    const files = fs.readdirSync(current, { withFileTypes: true });
    for (const f of files) {
      const full = path.join(current, f.name);
      if (f.isDirectory()) {
        walk(full);
      } else if (f.name.endsWith('.svelte')) {
        const content = fs.readFileSync(full, 'utf-8');
        // Parse <button ...> while properly respecting { ... } expressions containing >
        let pos = 0;
        while ((pos = content.indexOf('<button', pos)) !== -1) {
          totalButtons++;
          let end = pos + 7;
          let inBrace = 0;
          let inQuote = null;
          while (end < content.length) {
            const char = content[end];
            if (inQuote) {
              if (char === inQuote && content[end - 1] !== '\\') inQuote = null;
            } else if (char === '"' || char === "'") {
              inQuote = char;
            } else if (char === '{') {
              inBrace++;
            } else if (char === '}') {
              if (inBrace > 0) inBrace--;
            } else if (char === '>' && inBrace === 0) {
              break;
            }
            end++;
          }
          const fullTag = content.substring(pos, end + 1);
          pos = end + 1;
          const hasOnClick = /onclick\s*=/i.test(fullTag) || /on:click/i.test(fullTag);
          const isSubmit = /type\s*=\s*['"]submit['"]/i.test(fullTag);
          const isReset = /type\s*=\s*['"]reset['"]/i.test(fullTag);
          const hasProps = /\{\.\.\./.test(fullTag);
          if (!hasOnClick && !isSubmit && !isReset && !hasProps) {
            issues.push({ file: full, tag: fullTag.slice(0, 100) });
          }
        }
      }
    }
  }

  walk(dir);
  return { totalButtons, issues };
}

console.log('--- SCAN ZERO SILENT FAILURE BUTTON AUDIT ---');
const pqtm = scanButtonsInDir('D:/laragon/www/yayasan-pqtm/frontend/src');
console.log('SIM PQTM buttons:', pqtm.totalButtons, 'issues:', pqtm.issues.length);
if (pqtm.issues.length > 0) console.log(JSON.stringify(pqtm.issues, null, 2));

const hyperion = scanButtonsInDir('D:/laragon/www/hyperion-vault/frontend/src');
console.log('Hyperion Vault buttons:', hyperion.totalButtons, 'issues:', hyperion.issues.length);
if (hyperion.issues.length > 0) console.log(JSON.stringify(hyperion.issues, null, 2));
