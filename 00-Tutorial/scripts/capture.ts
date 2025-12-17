#!/usr/bin/env bun
/**
 * Playwright capture script for SPE Tutorial chapters
 * Reads URLs from tutorial.txt (format: duration|URL|text)
 * Forces full page reload for each GitHub URL to ensure line highlighting works
 *
 * Usage: bun run capture [chapter]
 * Example: bun run capture 01-Simple
 */

import { chromium, type Page } from 'playwright';
import { readFileSync, mkdirSync, readdirSync } from 'fs';
import { dirname, join, basename } from 'path';

interface Segment {
  duration: string;
  url: string;
  text: string;
  action?: string;  // Optional action: click:.selector, wait:1000, etc.
}

// Resolve SPE directory (parent of 00-Tutorial/)
// import.meta.dir = .../spe/00-Tutorial/scripts
// tutorialDir = .../spe/00-Tutorial
// speDir = .../spe
const tutorialDir = dirname(import.meta.dir);
const speDir = dirname(tutorialDir);

function getChapter(): string {
  const arg = process.argv[2];
  if (arg) return arg;

  console.error('Usage: bun run capture <chapter>');
  console.error('Example: bun run capture 01-Simple');
  process.exit(1);
}

function parseScript(scriptFile: string): Segment[] {
  const segments: Segment[] = [];
  const content = readFileSync(scriptFile, 'utf-8');

  for (const line of content.split('\n')) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) continue;

    const parts = trimmed.split('|');
    if (parts.length >= 3) {
      segments.push({
        duration: parts[0],
        url: parts[1],
        text: parts[2].slice(0, 50),
        action: parts[3]?.trim() || undefined  // Optional 4th field for actions
      });
    }
  }
  return segments;
}

// Execute an action on the page
async function executeAction(page: Page, action: string): Promise<void> {
  const [cmd, ...args] = action.split(':');
  const arg = args.join(':');  // Rejoin in case selector has colons

  switch (cmd) {
    case 'click':
      // click:.selector or click:#id
      await page.click(arg);
      await page.waitForTimeout(500);  // Wait for any animations
      break;
    case 'wait':
      // wait:1000 (milliseconds)
      await page.waitForTimeout(parseInt(arg) || 1000);
      break;
    case 'eval':
      // eval:SPE.toggleTheme() - run arbitrary JS
      await page.evaluate(arg);
      await page.waitForTimeout(500);
      break;
    default:
      console.warn(`Unknown action: ${cmd}`);
  }
}

async function hideGitHubHeaders(page: Page): Promise<void> {
  await page.evaluate(() => {
    const selectors = [
      '.AppHeader',
      '.js-header-wrapper',
      'header[role="banner"]',
      '.Header',
      '.px-3.px-md-4.px-lg-5'
    ];
    selectors.forEach(sel => {
      document.querySelectorAll<HTMLElement>(sel).forEach(el => {
        el.style.position = 'absolute';
        el.style.top = '-200px';
      });
    });
  });
}

async function scrollToLine(page: Page, lineNum: string): Promise<void> {
  await page.evaluate((ln) => {
    const lineEl = document.querySelector(`[data-line-number="${ln}"]`);
    if (lineEl) {
      const rect = lineEl.getBoundingClientRect();
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      const targetY = scrollTop + rect.top - (window.innerHeight / 3);
      window.scrollTo({ top: targetY, behavior: 'instant' });
    }
  }, lineNum);
}

async function scrollToCodeTop(page: Page): Promise<void> {
  await page.evaluate(() => {
    const codeArea = document.querySelector('.react-code-lines') ||
                     document.querySelector('.js-file-line-container') ||
                     document.querySelector('.blob-wrapper');
    if (codeArea) {
      codeArea.scrollIntoView({ block: 'start', behavior: 'instant' });
    }
  });
}

async function main(): Promise<void> {
  const chapter = getChapter();
  const chapterDir = join(speDir, chapter);
  const framesDir = join(chapterDir, 'frames');
  const scriptFile = join(chapterDir, 'tutorial.txt');

  mkdirSync(framesDir, { recursive: true });

  const segments = parseScript(scriptFile);
  console.log(`=== Capturing ${segments.length} frames for Chapter ${chapter} ===`);
  console.log(`Source: ${scriptFile}`);
  console.log(`Output: ${framesDir}`);
  console.log();

  const browser = await chromium.launch();

  // Context for GitHub - 300% zoom (1280x720 virtual -> 3840x2160 output)
  const githubContext = await browser.newContext({
    viewport: { width: 1280, height: 720 },
    colorScheme: 'light',
    deviceScaleFactor: 3.0
  });

  // Context for local demo - 300% zoom (1280x720 virtual -> 3840x2160 output)
  const demoContext = await browser.newContext({
    viewport: { width: 1280, height: 720 },
    colorScheme: 'light',
    deviceScaleFactor: 3.0
  });

  const ghPage = await githubContext.newPage();
  const demoPage = await demoContext.newPage();

  for (let i = 0; i < segments.length; i++) {
    const seg = segments[i];
    const frameNum = i + 1;
    const isGitHub = seg.url.includes('github.com');

    process.stdout.write(`[${frameNum}] ${seg.text}... `);

    let page: Page;
    if (isGitHub) {
      page = ghPage;
      // Force fresh page load by going to blank first
      await page.goto('about:blank');
      await page.waitForTimeout(100);
    } else {
      page = demoPage;
    }

    await page.goto(seg.url, { waitUntil: 'domcontentloaded', timeout: 60000 });

    // Wait for page to fully render
    const waitTime = isGitHub ? 2000 : 1000;
    await page.waitForTimeout(waitTime);

    if (isGitHub) {
      await hideGitHubHeaders(page);

      // For URLs with line anchors, scroll to show that line
      const lineMatch = seg.url.match(/#L(\d+)/);
      if (lineMatch) {
        await scrollToLine(page, lineMatch[1]);
      } else {
        await scrollToCodeTop(page);
      }

      await page.waitForTimeout(300);
    }

    // Execute optional action before screenshot (e.g., click:.theme-toggle)
    if (seg.action) {
      process.stdout.write(`[${seg.action}] `);
      await executeAction(page, seg.action);
    }

    const output = join(framesDir, `frame_${String(frameNum).padStart(3, '0')}.png`);
    await page.screenshot({ path: output, fullPage: false });
    console.log('done');
  }

  await ghPage.close();
  await demoPage.close();
  await browser.close();

  console.log();
  console.log('=== Capture complete ===');
  const files = readdirSync(framesDir).filter(f => f.endsWith('.png'));
  console.log(`${files.length} frames captured`);
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
