#!/usr/bin/env bun
/**
 * Interactive demo capture script for SPE Tutorial chapters
 * Use with codegen: bunx playwright codegen http://localhost:8080/08-Users
 * Paste generated code into the chapter's demo function below
 *
 * Usage: bun run demo [chapter] [--video]
 * Example: bun run demo 08-Users
 *          bun run demo 09-Blog --video
 */

import { chromium, type Page, type BrowserContext } from 'playwright';
import { mkdirSync, readdirSync } from 'fs';
import { dirname, join } from 'path';

interface DemoConfig {
  chapter: string;
  baseUrl: string;
  recordVideo: boolean;
  slowMo: number;
  viewport: { width: number; height: number };
  deviceScaleFactor: number;
}

// Resolve SPE directory (parent of 00-Tutorial/)
// import.meta.dir = .../spe/00-Tutorial/scripts
// tutorialDir = .../spe/00-Tutorial
// speDir = .../spe
const tutorialDir = dirname(import.meta.dir);
const speDir = dirname(tutorialDir);

const config: DemoConfig = {
  chapter: process.argv[2] || '01-Simple',
  baseUrl: 'http://localhost:8080',
  recordVideo: process.argv.includes('--video'),
  slowMo: 100,
  viewport: { width: 1280, height: 720 },  // 300% zoom -> 3840x2160 output
  deviceScaleFactor: 3.0
};

// Frame counter for sequential naming
let frameCount = 0;
let framesDir: string;

/**
 * Capture a screenshot with auto-incrementing frame number
 */
async function capture(page: Page, description?: string): Promise<void> {
  frameCount++;
  const filename = `frame_${String(frameCount).padStart(3, '0')}.png`;
  const filepath = join(framesDir, filename);
  await page.screenshot({ path: filepath, fullPage: false });
  console.log(`[${frameCount}] ${description || 'Captured'}`);
}

/**
 * Pause for narration timing
 */
async function pause(page: Page, ms: number = 2000): Promise<void> {
  await page.waitForTimeout(ms);
}

/**
 * Chapter demo definitions - paste codegen output here
 * Each chapter can have its own interactive demo sequence
 */
const demos: Record<string, (page: Page) => Promise<void>> = {

  '01-Simple': async (page) => {
    await page.goto(`${config.baseUrl}/01-Simple`);
    await capture(page, 'Home page');
    await pause(page);

    await page.getByRole('link', { name: 'About' }).click();
    await capture(page, 'About page');
    await pause(page);

    await page.getByRole('link', { name: 'Contact' }).click();
    await capture(page, 'Contact page');
  },

  '08-Users': async (page) => {
    // Example: paste codegen output for user CRUD demo
    await page.goto(`${config.baseUrl}/08-Users`);
    await capture(page, 'Users list');
    await pause(page);

    // Add user flow (replace with your codegen output)
    // await page.getByRole('link', { name: 'Add User' }).click();
    // await capture(page, 'Add user form');
    // await page.getByLabel('Username').fill('alice');
    // await page.getByLabel('Email').fill('alice@example.com');
    // await page.getByLabel('Password').fill('secret123');
    // await page.getByRole('button', { name: 'Create' }).click();
    // await capture(page, 'User created');
  },

  '09-Blog': async (page) => {
    // Example: paste codegen output for blog CMS demo
    await page.goto(`${config.baseUrl}/09-Blog`);
    await capture(page, 'Blog home');
    await pause(page);

    // Login flow (replace with your codegen output)
    // await page.getByRole('link', { name: 'Login' }).click();
    // await capture(page, 'Login form');
    // await page.getByLabel('Email').fill('admin@example.com');
    // await page.getByLabel('Password').fill('admin');
    // await page.getByRole('button', { name: 'Sign in' }).click();
    // await capture(page, 'Dashboard');

    // Create post flow
    // await page.getByRole('link', { name: 'New Post' }).click();
    // await capture(page, 'New post form');
  }
};

async function main(): Promise<void> {
  const chapterDir = join(speDir, config.chapter);
  framesDir = join(chapterDir, 'frames');
  const recordingsDir = join(chapterDir, 'recordings');

  mkdirSync(framesDir, { recursive: true });
  mkdirSync(recordingsDir, { recursive: true });

  // Check for existing frames to continue numbering
  const existingFrames = readdirSync(framesDir).filter(f => f.match(/^frame_\d+\.png$/));
  frameCount = existingFrames.length;

  if (frameCount > 0) {
    console.log(`Found ${frameCount} existing frames, continuing from frame_${String(frameCount + 1).padStart(3, '0')}.png`);
  }

  console.log(`=== Interactive Demo: ${config.chapter} ===`);
  console.log(`Base URL: ${config.baseUrl}`);
  console.log(`Video recording: ${config.recordVideo}`);
  console.log();

  const browser = await chromium.launch({
    headless: false,
    slowMo: config.slowMo
  });

  const contextOptions: Parameters<typeof browser.newContext>[0] = {
    viewport: config.viewport,
    colorScheme: 'light',
    deviceScaleFactor: config.deviceScaleFactor
  };

  if (config.recordVideo) {
    contextOptions.recordVideo = {
      dir: recordingsDir,
      size: { width: 3840, height: 2160 }
    };
  }

  const context = await browser.newContext(contextOptions);
  const page = await context.newPage();

  // Run the demo for this chapter
  const demo = demos[config.chapter];
  if (demo) {
    try {
      await demo(page);
      console.log();
      console.log('=== Demo complete ===');
    } catch (err) {
      console.error('Demo error:', err);
    }
  } else {
    console.log(`No demo defined for ${config.chapter}`);
    console.log('Opening browser for manual interaction...');
    console.log('Press Ctrl+C to exit when done.');

    await page.goto(`${config.baseUrl}/${config.chapter}`);

    // Keep browser open for manual interaction
    await new Promise(() => {});
  }

  await context.close();
  await browser.close();

  const totalFrames = readdirSync(framesDir).filter(f => f.match(/^frame_\d+\.png$/)).length;
  console.log(`Total frames: ${totalFrames}`);

  if (config.recordVideo) {
    console.log(`Video saved to: ${recordingsDir}`);
  }
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
