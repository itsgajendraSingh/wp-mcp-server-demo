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
```http://<your-site>/wp-json/site-content-server/mcp/```

If the server is registered correctly, you’ll see a `401 Unauthorized` response.
``` response
{"code":"rest_forbidden","message":"Sorry, you are not allowed to do that.","data":{"status":401}}
```

## Testing Tools via Terminal
```curl
curl -X POST http://<your-site>/wp-json/site-content-server/mcp/
-H "Content-Type: application/json"
-H "Mcp-Session-Id: session-id"
-u admin:APP_PASSWORD
-d '{
"jsonrpc": "2.0",
"id": 1,
"method": "tools/list"
}'
```

---
## Notes

- This is a minimal example intended for learning.
