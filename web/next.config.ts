import type { NextConfig } from "next";

// Bilder serveras av Laravel-appen bakom API_URL (public disk).
const api = new URL(process.env.API_URL ?? "http://localhost:8000");
// Bildoptimeraren vägrar privata IP-adresser (SSRF-skydd); tillåt dem bara när API:et kör lokalt.
const localApi = ["localhost", "127.0.0.1", "[::1]"].includes(api.hostname);

const nextConfig: NextConfig = {
  images: {
    dangerouslyAllowLocalIP: localApi,
    remotePatterns: [
      {
        protocol: api.protocol === "https:" ? "https" : "http",
        hostname: api.hostname,
        port: api.port,
        pathname: "/**",
      },
    ],
  },
};

export default nextConfig;
