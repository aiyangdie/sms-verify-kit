// Package smsverifykit — Aliyun PNVS SMS verification Go SDK
package smsverifykit

import (
	"crypto/hmac"
	"crypto/rand"
	"crypto/sha1"
	"encoding/base64"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"regexp"
	"sort"
	"strings"
	"time"
)

const apiVersion = "2017-05-25"

var defaultTemplates = map[string]string{
	"login":          "100001",
	"register":       "100001",
	"bind_phone":     "100004",
	"change_phone":   "100002",
	"reset_password": "100001",
}

var errorMap = map[string]string{
	"OFFER_NOT_ORDER_STATUS":     "请先在阿里云控制台开通「短信认证」",
	"biz.FREQUENCY":                "发送太频繁，请稍后再试",
	"FREQUENCY_FAIL":               "发送太频繁，请稍后再试",
	"MOBILE_NUMBER_ILLEGAL":        "手机号格式不正确",
	"BUSINESS_LIMIT_CONTROL":       "该号码今日发送次数已达上限",
	"InvalidSignName":              "签名无效，请检查 SignName 配置",
	"isv.SMS_TEMPLATE_ILLEGAL":     "模板无效，请检查 TemplateCode",
	"NETWORK":                      "网络异常，请稍后重试",
}

type Config struct {
	AccessKeyID     string
	AccessKeySecret string
	SignName        string
	Endpoint        string
	CountryCode     string
	CodeLength      int
	ValidSeconds    int
	IntervalSeconds int
	Templates       map[string]string
}

type Client struct {
	cfg Config
}

type Result struct {
	OK      bool   `json:"ok"`
	Message string `json:"message"`
	BizID   string `json:"biz_id,omitempty"`
	Code    string `json:"code,omitempty"`
	Pass    bool   `json:"pass,omitempty"`
}

func NewClient(cfg Config) *Client {
	if cfg.Endpoint == "" {
		cfg.Endpoint = "https://dypnsapi.aliyuncs.com"
	}
	if cfg.CountryCode == "" {
		cfg.CountryCode = "86"
	}
	if cfg.CodeLength == 0 {
		cfg.CodeLength = 4
	}
	if cfg.ValidSeconds == 0 {
		cfg.ValidSeconds = 300
	}
	if cfg.IntervalSeconds == 0 {
		cfg.IntervalSeconds = 60
	}
	if cfg.Templates == nil {
		cfg.Templates = map[string]string{}
	}
	for k, v := range defaultTemplates {
		if _, ok := cfg.Templates[k]; !ok {
			cfg.Templates[k] = v
		}
	}
	return &Client{cfg: cfg}
}

func NormalizePhone(phone string) string {
	re := regexp.MustCompile(`\D`)
	digits := re.ReplaceAllString(strings.TrimSpace(phone), "")
	if len(digits) == 11 && digits[0] == '1' {
		return digits
	}
	return ""
}

func (c *Client) templateForScene(scene string) string {
	if t, ok := c.cfg.Templates[scene]; ok {
		return t
	}
	return c.cfg.Templates["login"]
}

func (c *Client) Send(phone, scene string) Result {
	if scene == "" {
		scene = "login"
	}
	phone = NormalizePhone(phone)
	if phone == "" {
		return Result{OK: false, Message: "手机号格式无效"}
	}
	minutes := (c.cfg.ValidSeconds + 59) / 60
	if minutes < 1 {
		minutes = 1
	}
	tplParam, _ := json.Marshal(map[string]string{"code": "##code##", "min": fmt.Sprintf("%d", minutes)})
	resp, err := c.call("SendSmsVerifyCode", map[string]string{
		"PhoneNumber":   phone,
		"SignName":      c.cfg.SignName,
		"TemplateCode":  c.templateForScene(scene),
		"TemplateParam": string(tplParam),
		"CodeType":      "1",
		"CodeLength":    fmt.Sprintf("%d", c.cfg.CodeLength),
		"ValidTime":     fmt.Sprintf("%d", c.cfg.ValidSeconds),
		"Interval":      fmt.Sprintf("%d", c.cfg.IntervalSeconds),
		"CountryCode":   c.cfg.CountryCode,
	})
	if err != nil {
		return Result{OK: false, Message: err.Error(), Code: "NETWORK"}
	}
	if resp["Code"] == "OK" {
		if success, _ := resp["Success"].(bool); success {
			bizID := ""
			if m, ok := resp["Model"].(map[string]interface{}); ok {
				bizID, _ = m["BizId"].(string)
			}
			return Result{OK: true, Message: "验证码已发送", BizID: bizID}
		}
	}
	return Result{OK: false, Message: humanError(resp), Code: fmt.Sprintf("%v", resp["Code"])}
}

func (c *Client) Verify(phone, code string) Result {
	phone = NormalizePhone(phone)
	code = strings.ReplaceAll(strings.TrimSpace(code), " ", "")
	if phone == "" || code == "" {
		return Result{OK: false, Message: "手机号或验证码不能为空"}
	}
	resp, err := c.call("CheckSmsVerifyCode", map[string]string{
		"PhoneNumber":    phone,
		"VerifyCode":     code,
		"CountryCode":    c.cfg.CountryCode,
		"CaseAuthPolicy": "1",
	})
	if err != nil {
		return Result{OK: false, Message: err.Error(), Code: "NETWORK"}
	}
	pass := false
	if m, ok := resp["Model"].(map[string]interface{}); ok {
		pass, _ = m["VerifyResult"].(string) == "PASS"
	}
	if resp["Code"] == "OK" && pass {
		return Result{OK: true, Message: "验证成功", Pass: true}
	}
	msg := "验证码错误或已过期"
	if pass {
		msg = humanError(resp)
	}
	return Result{OK: false, Message: msg, Pass: false}
}

func (c *Client) call(action string, extra map[string]string) (map[string]interface{}, error) {
	nonce := make([]byte, 8)
	_, _ = rand.Read(nonce)
	params := map[string]string{
		"Action":           action,
		"Format":           "json",
		"Version":          apiVersion,
		"SignatureMethod":  "HMAC-SHA1",
		"Timestamp":        time.Now().UTC().Format("2006-01-02T15:04:05Z"),
		"SignatureVersion": "1.0",
		"SignatureNonce":   hex.EncodeToString(nonce),
		"AccessKeyId":      c.cfg.AccessKeyID,
	}
	for k, v := range extra {
		params[k] = v
	}
	params["Signature"] = sign(params, c.cfg.AccessKeySecret)
	u, _ := url.Parse(strings.TrimRight(c.cfg.Endpoint, "/"))
	q := u.Query()
	for k, v := range params {
		q.Set(k, v)
	}
	u.RawQuery = q.Encode()
	client := &http.Client{Timeout: 15 * time.Second}
	res, err := client.Get(u.String())
	if err != nil {
		return nil, fmt.Errorf("无法连接短信服务")
	}
	defer res.Body.Close()
	body, _ := io.ReadAll(res.Body)
	var data map[string]interface{}
	if err := json.Unmarshal(body, &data); err != nil {
		return nil, fmt.Errorf("响应解析失败")
	}
	return data, nil
}

func sign(params map[string]string, secret string) string {
	keys := make([]string, 0, len(params))
	for k := range params {
		if k != "Signature" {
			keys = append(keys, k)
		}
	}
	sort.Strings(keys)
	var pairs []string
	for _, k := range keys {
		pairs = append(pairs, percentEncode(k)+"="+percentEncode(params[k]))
	}
	stringToSign := "GET&" + percentEncode("/") + "&" + percentEncode(strings.Join(pairs, "&"))
	mac := hmac.New(sha1.New, []byte(secret+"&"))
	mac.Write([]byte(stringToSign))
	return base64.StdEncoding.EncodeToString(mac.Sum(nil))
}

func percentEncode(s string) string {
	enc := url.QueryEscape(s)
	enc = strings.ReplaceAll(enc, "+", "%20")
	enc = strings.ReplaceAll(enc, "*", "%2A")
	enc = strings.ReplaceAll(enc, "%7E", "~")
	return enc
}

func humanError(resp map[string]interface{}) string {
	code, _ := resp["Code"].(string)
	msg, _ := resp["Message"].(string)
	if m, ok := errorMap[code]; ok {
		return m
	}
	if msg != "" && msg != "UNKNOWN" {
		return msg
	}
	if code == "" {
		code = "未知"
	}
	return "短信服务异常（" + code + "）"
}
