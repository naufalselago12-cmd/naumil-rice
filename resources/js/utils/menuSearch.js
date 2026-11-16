/**
 * Pencarian menu toleran typo (fuzzy string search) menggunakan:
 * - Normalisasi string & spasi
 * - Kecocokan substring & awalan (prefix)
 * - Damerau-Levenshtein (mendukung huruf tertukar, kurang, lebih, atau salah ketik)
 * - Dice Bigram similarity
 */

export function normalizeText(value) {
  return String(value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

export function damerauLevenshtein(a, b) {
  const m = a.length;
  const n = b.length;
  if (m === 0) return n;
  if (n === 0) return m;

  const dp = Array.from({ length: m + 1 }, () => new Array(n + 1).fill(0));
  for (let i = 0; i <= m; i++) dp[i][0] = i;
  for (let j = 0; j <= n; j++) dp[0][j] = j;

  for (let i = 1; i <= m; i++) {
    for (let j = 1; j <= n; j++) {
      const cost = a[i - 1] === b[j - 1] ? 0 : 1;
      let val = Math.min(
        dp[i - 1][j] + 1,
        dp[i][j - 1] + 1,
        dp[i - 1][j - 1] + cost
      );
      if (i > 1 && j > 1 && a[i - 1] === b[j - 2] && a[i - 2] === b[j - 1]) {
        val = Math.min(val, dp[i - 2][j - 2] + cost);
      }
      dp[i][j] = val;
    }
  }
  return dp[m][n];
}

export function bigramSimilarity(a, b) {
  if (a === b) return 1;
  if (a.length < 2 || b.length < 2) return 0;

  const bigramsA = new Map();
  for (let i = 0; i < a.length - 1; i++) {
    const bg = a.slice(i, i + 2);
    bigramsA.set(bg, (bigramsA.get(bg) || 0) + 1);
  }

  let overlap = 0;
  for (let i = 0; i < b.length - 1; i++) {
    const bg = b.slice(i, i + 2);
    const count = bigramsA.get(bg) || 0;
    if (count > 0) {
      overlap++;
      bigramsA.set(bg, count - 1);
    }
  }

  return (2 * overlap) / (a.length - 1 + (b.length - 1));
}

function tokenMaxAllowedEdits(len) {
  if (len <= 2) return 0;
  if (len <= 4) return 1;
  if (len <= 7) return 2;
  return 3;
}

function scoreTokenAgainstWord(qToken, word) {
  if (!word || !qToken) return 0;
  if (word === qToken) return 1.0;
  if (word.startsWith(qToken)) return 0.96;
  if (word.includes(qToken)) return 0.90;

  const qLen = qToken.length;
  const maxEdits = tokenMaxAllowedEdits(qLen);

  // Cocokkan terhadap seluruh kata
  const fullDist = damerauLevenshtein(qToken, word);
  if (fullDist <= maxEdits) {
    return Math.max(0.60, 0.88 - fullDist * 0.14);
  }

  // Cocokkan terhadap awalan kata jika user mengetik sebagian kata yang typo (mis. "goregg" vs "goreng")
  if (qLen >= 3 && word.length > qLen) {
    const slice = word.slice(0, Math.min(word.length, qLen + 1));
    const prefDist = damerauLevenshtein(qToken, slice);
    if (prefDist <= Math.max(1, maxEdits - 1)) {
      return Math.max(0.58, 0.84 - prefDist * 0.15);
    }
  }

  // Fallback bigram similarity
  if (qLen >= 3) {
    const sim = bigramSimilarity(qToken, word);
    if (sim >= 0.45) {
      return 0.50 + sim * 0.32;
    }
  }

  return 0;
}

function scoreTokenAgainstText(qToken, text, fieldWeight = 1.0) {
  if (!text) return 0;
  const words = text.split(' ').filter(Boolean);
  let best = 0;

  for (const w of words) {
    const s = scoreTokenAgainstWord(qToken, w);
    if (s > best) best = s;
  }

  // Cek juga string tanpa spasi agar "esteh" cocok dengan "es teh", "nasigoreng" dengan "nasi goreng"
  const compactText = text.replace(/\s+/g, '');
  if (qToken.length >= 4) {
    if (compactText.includes(qToken)) {
      best = Math.max(best, 0.88);
    } else if (compactText.length <= qToken.length + 4) {
      const d = damerauLevenshtein(qToken, compactText);
      if (d <= tokenMaxAllowedEdits(qToken.length)) {
        best = Math.max(best, Math.max(0.60, 0.86 - d * 0.12));
      }
    }
  }

  return best * fieldWeight;
}

/**
 * Mencari menu paling relevan & terdekat berdasarkan query string.
 *
 * @param {Array<Object>} menus
 * @param {string} rawQuery
 * @returns {{ items: Array<Object>, isExact: boolean, hadFuzzyMatch: boolean, bestSuggestion: string|null }}
 */
export function fuzzySearchMenus(menus, rawQuery) {
  const normalizedQuery = normalizeText(rawQuery);
  if (!normalizedQuery) {
    return {
      items: Array.isArray(menus) ? menus : [],
      isExact: true,
      hadFuzzyMatch: false,
      bestSuggestion: null,
    };
  }

  const queryTokens = normalizedQuery.split(' ').filter(Boolean);
  const compactQuery = normalizedQuery.replace(/\s+/g, '');

  const scored = [];
  let exactHits = 0;

  for (const menu of menus || []) {
    const nameNorm = normalizeText(menu.name);
    const descNorm = normalizeText(menu.description);
    const catNorm = normalizeText(menu.category?.name);
    const compactName = nameNorm.replace(/\s+/g, '');

    const isDirectSubstring =
      nameNorm.includes(normalizedQuery) ||
      compactName.includes(compactQuery) ||
      descNorm.includes(normalizedQuery) ||
      catNorm.includes(normalizedQuery);

    if (isDirectSubstring) {
      exactHits++;
      const startsMatch = nameNorm.startsWith(normalizedQuery);
      const nameExact = nameNorm === normalizedQuery;
      const bonus = nameExact ? 3.0 : (startsMatch ? 2.5 : (nameNorm.includes(normalizedQuery) ? 2.1 : 1.5));
      scored.push({ menu, score: bonus, isDirect: true });
      continue;
    }

    // Evaluasi setiap token query secara toleran typo
    let totalScore = 0;
    let strongTokenCount = 0;

    for (const token of queryTokens) {
      const sName = scoreTokenAgainstText(token, nameNorm, 1.0);
      const sCat = scoreTokenAgainstText(token, catNorm, 0.85);
      const sDesc = scoreTokenAgainstText(token, descNorm, 0.72);
      const bestToken = Math.max(sName, sCat, sDesc);

      if (bestToken >= 0.55) {
        strongTokenCount++;
      }
      totalScore += bestToken;
    }

    // Bandingkan juga full compact query vs nama menu rapat (mis. "nasgorr" vs "nasigoreng")
    if (compactQuery.length >= 4 && compactName.length >= 3) {
      const fullBg = bigramSimilarity(compactQuery, compactName);
      if (fullBg >= 0.45) {
        totalScore = Math.max(totalScore / Math.max(1, queryTokens.length), fullBg * 0.9) * queryTokens.length;
        if (strongTokenCount === 0 && fullBg >= 0.50) {
          strongTokenCount = 1;
        }
      }
    }

    const avgScore = totalScore / Math.max(1, queryTokens.length);
    const minMatchedTokens = queryTokens.length === 1 ? 1 : Math.ceil(queryTokens.length * 0.6);

    if (strongTokenCount >= minMatchedTokens && avgScore >= 0.54) {
      scored.push({ menu, score: avgScore, isDirect: false });
    }
  }

  scored.sort((a, b) => {
    if (b.score !== a.score) return b.score - a.score;
    return String(a.menu.name || '').localeCompare(String(b.menu.name || ''));
  });

  const items = scored.map((entry) => entry.menu);
  const hadFuzzyMatch = exactHits === 0 && items.length > 0;
  const bestSuggestion = hadFuzzyMatch ? items[0]?.name || null : null;

  return {
    items,
    isExact: exactHits > 0,
    hadFuzzyMatch,
    bestSuggestion,
  };
}

