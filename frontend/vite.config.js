import tailwindcss from '@tailwindcss/vite';
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';
import path from 'node:path';
import fs from 'node:fs';

// Dynamically determine local backend URL from backend/.env or parent folder name
function getBackendTarget() {
	const baseDir = import.meta.dirname;
	try {
		const envPath = path.resolve(baseDir, '../backend/.env');
		if (fs.existsSync(envPath)) {
			const envContent = fs.readFileSync(envPath, 'utf-8');
			const match = envContent.match(/^APP_URL=(.+)$/m);
			if (match && match[1]) {
				const appUrl = match[1].trim().replace(/['"]/g, '');
				if (appUrl) return appUrl;
			}
		}
	} catch {
		// Fallback to dynamic host calculation below
	}
	const folderName = path.basename(path.resolve(baseDir, '..'));
	return `http://${folderName.toLowerCase().replace(/[^a-zA-Z0-9_.-]/g, '-')}.test`;
}

const backendTarget = getBackendTarget();

export default defineConfig({
	server: { 
		host: '127.0.0.1', 
		port: 5173,
		allowedHosts: true,
		hmr: {
			host: 'localhost',
			port: 5173
		},
		proxy: {
			'/api': {
				target: backendTarget,
				changeOrigin: true
			},
			'/sanctum': {
				target: backendTarget,
				changeOrigin: true
			}
		}
	},
	plugins: [
		tailwindcss(),
		sveltekit()
	]
});
