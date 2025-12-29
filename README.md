# WordPress MCP + Abilities API Example

This repository demonstrates how to expose WordPress functionality as MCP tools using the WordPress Abilities API and the MCP Adapter plugin.

**The example shows how to:**
- Register Ability Categories
- Register Abilities with input/output schemas
- Make abilities discoverable by AI agents
- Attach abilities to an MCP Server
- Test the server using MCP JSON-RPC requests

---

## Requirements

- WordPress 6.9+
- MCP Adapter plugin  
  https://github.com/WordPress/mcp-adapter
- Run `composer install` inside the MCP Adapter plugin directory

---

## What This Example Does

This example exposes a **Create Post** ability that allows an AI agent to:
- Create WordPress posts
- Control post status (draft/publish)
- Receive the created post URL as output

The ability is marked as **public**, so MCP-compatible agents can discover and use it.

---

## Usage

1. Copy the code from `example-abilities.php`
2. Paste it into your theme’s `functions.php` file or a custom plugin
3. Ensure the MCP Adapter plugin is active
4. **Access the MCP endpoint:**
```http://<your-site>/wp-json/site-content-server/```

If the server is registered correctly, you’ll see a response similar to the following:
``` response
{"namespace":"site-content-server","routes":{"\/site-content-server":{"namespace":"site-content-server","methods":["GET"],"endpoints":[{"methods":["GET"],"args":{"namespace":{"default":"site-content-server","required":false},"context":{"default":"view","required":false}}}],"_links":{"self":[{"href":"http:\/\/localhost:10077\/wp-json\/site-content-server"}]}},"\/site-content-server\/mcp":{"namespace":"site-content-server","methods":["POST","GET","DELETE"],"endpoints":[{"methods":["POST","GET","DELETE"],"args":[]}],"_links":{"self":[{"href":"http:\/\/localhost:10077\/wp-json\/site-content-server\/mcp"}]}}},"_links":{"up":[{"href":"http:\/\/localhost:10077\/wp-json\/"}]}}
```

## Testing Tools via Terminal
```curl
curl -X POST http://<your-site>/wp-json/site-content-server/mcp/
-H "Content-Type: application/json"
-H "Mcp-Session-Id: session-id"
-u username:appPassword
-d '{
"jsonrpc": "2.0",
"id": 1,
"method": "tools/list"
}'
```

---
## Notes

- This is a minimal example intended for learning.
