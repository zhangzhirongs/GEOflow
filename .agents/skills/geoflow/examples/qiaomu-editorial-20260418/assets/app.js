const PACKAGE_META = Object.freeze([
  { text: 'Qiaomu Editorial', accent: true },
  { text: '参考：经过清理的本地示例', accent: false },
  { text: 'MVP：首页 → 文章', accent: false }
]);

function loadPackageMeta() {
  const mount = document.getElementById('packageMeta');
  if (!mount) return;
  for (const item of PACKAGE_META) {
    const chip = document.createElement('div');
    chip.className = item.accent ? 'chip chip--accent' : 'chip';
    chip.textContent = item.text;
    mount.appendChild(chip);
  }
}
window.addEventListener('DOMContentLoaded', loadPackageMeta);
