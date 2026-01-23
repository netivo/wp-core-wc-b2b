import { registerBlockType } from "@wordpress/blocks";

import Edit from "./edit";

registerBlockType( "netivo/register-form", {
  edit: Edit,
  save: () => {
    return null;
  }
} );
