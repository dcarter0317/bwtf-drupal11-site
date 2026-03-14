<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* @bwtf/templates/layout/footer.html.twig */
class __TwigTemplate_01987a7f9a9af00f68c170bf5a691a66 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        yield "<footer>
  <div class=\"container\">
    <p>BWTF.COM is copyright of Benson Yee since 1997. HASBRO, TRANSFORMERS and all related characters are trademarks of Hasbro. , Takara Ltd. and Tomy. Direct linking of images is not allowed.
      If you wish to use any content from this site, <a href=\"https://www.bwtf.com/contact\" target=\"_blank\" rel=\"noopener\">please request permission</a> before doing so.</p>
    
    ";
        // line 6
        if ((($tmp =  !Twig\Extension\CoreExtension::testEmpty(($context["social_links"] ?? null))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 7
            yield "      <ul class=\"footer-social\">
        ";
            // line 8
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["social_links"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["link"]) {
                // line 9
                yield "          <li>
            <a href=\"";
                // line 10
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["link"], "url", [], "any", false, false, true, 10), "html", null, true);
                yield "\" 
               target=\"_blank\" 
               rel=\"noopener noreferrer\" 
               aria-label=\"";
                // line 13
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["link"], "label", [], "any", false, false, true, 13), "html", null, true);
                yield "\">
              <i class=\"";
                // line 14
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["link"], "icon", [], "any", false, false, true, 14), "html", null, true);
                yield "\" aria-hidden=\"true\"></i>
            </a>
          </li>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['link'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 18
            yield "      </ul>
    ";
        }
        // line 20
        yield "    
    <p>Theme by Dave Carter for <a href=\"https://www.dcartdevelopment.com\" target=\"_blank\" rel=\"noopener\">https://www.dcartdevelopment.com</a></p>
  </div>
</footer>
";
        // line 24
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("bwtf/social-links"), "html", null, true);
        yield "
<script src=\"";
        // line 25
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, (($context["base_path"] ?? null) . ($context["directory"] ?? null)), "html", null, true);
        yield "/assets/js/bwtf_v2.js\"></script> 
</body>";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["social_links", "base_path", "directory"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@bwtf/templates/layout/footer.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  97 => 25,  93 => 24,  87 => 20,  83 => 18,  73 => 14,  69 => 13,  63 => 10,  60 => 9,  56 => 8,  53 => 7,  51 => 6,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@bwtf/templates/layout/footer.html.twig", "/var/www/html/web/themes/custom/bwtf/templates/layout/footer.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 6, "for" => 8];
        static $filters = ["escape" => 10];
        static $functions = ["attach_library" => 24];

        try {
            $this->sandbox->checkSecurity(
                ['if', 'for'],
                ['escape'],
                ['attach_library'],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
