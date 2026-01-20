import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';
import { TextControl } from '@wordpress/components';

export default function Edit({ clientId, attributes, setAttributes, isSelected }) {
  const blockProps = useBlockProps({
    className: 'block b-block'
  });
  const [isEditing, setIsEditing] = useState(false);

  const handleBlockClick = () => {
    if (!isSelected) return;
    setIsEditing(true);
  };

  // Funkcja obsługująca utratę focus
  const handleBlur = () => {
    setIsEditing(false);
  };

  return (
    <>
      <div {...blockProps}>
        {isEditing && isSelected ? (
          <div className="contact-form-edit-mode">
            <TextControl
              label={__('Treść zgody', 'netivo')}
              value={attributes.consent_text}
              onChange={(value) => setAttributes({ consent_text: value })}
              placeholder={__('Wprowadź treść zgody...', 'textdomain')}
              onBlur={handleBlur}
              autoFocus
            />
            <p className="edit-hint">
              {__('Kliknij poza polem lub naciśnij Enter aby zakończyć edycję', 'textdomain')}
            </p>
          </div>
        ) : (
          <div
            className={`contact-form-display ${isSelected ? 'selected' : ''}`}
            onClick={handleBlockClick}
          >
            <ServerSideRender
              block="netivo/contact-form"
              attributes={attributes}
            />
          </div>
        )}
      </div>
    </>
  );
}